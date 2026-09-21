<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookPrice;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Mail\OrderShippedMail;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    /** Adjust this list to match the actual status values used in your Order model/migration */
    private array $orderStatuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled', 'failed'];

    public function index()
    {
        $orders = Order::with(['user', 'items', 'latestTransaction'])
            ->latest()
            ->get();

        $stats = [
            'total'      => Order::count(),
            'pending'    => Order::where('status', 'pending')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'completed'  => Order::where('status', 'completed')->count(),
            'cancelled'  => Order::whereIn('status', ['cancelled', 'failed'])->count(),
        ];

        $statuses = $this->orderStatuses;

        return view('orders.index', compact('orders', 'stats', 'statuses'));
    }

    public function show(Order $order)
    {
        $order->load('items.book', 'addresses.country', 'transactions', 'user', 'currency');

        $statuses = $this->orderStatuses;

        return view('orders.show', compact('order', 'statuses'));
    }

   public function updateStatus(Request $request, Order $order): JsonResponse
{
    $rules = [
        'status' => 'required|in:' . implode(',', $this->orderStatuses),
    ];

    // tracking number is required only when moving TO 'shipped'
    if ($request->status === 'shipped') {
        $rules['tracking_number'] = 'required|string|max:100';
        $rules['shipping_carrier'] = 'nullable|string|max:100';
    }

    $data = $request->validate($rules);

    $wasShipped = $order->status === 'shipped'; // avoid re-sending mail on every save if already shipped
    $movingToShipped = $data['status'] === 'shipped' && ! $wasShipped;

    $updatePayload = ['status' => $data['status']];

    if ($data['status'] === 'shipped') {
        $updatePayload['tracking_number']  = $data['tracking_number'];
        $updatePayload['shipping_carrier'] = $data['shipping_carrier'] ?? null;
        $updatePayload['shipped_at']       = now();
    }

    $order->update($updatePayload);

    // send tracking email only the first time it moves into "shipped"
    if ($movingToShipped) {
        try {
            $order->load('user');
            Mail::to($order->user->email)->send(new OrderShippedMail($order));
        } catch (\Throwable $e) {
            \Log::error('Shipped email failed: ' . $e->getMessage());
        }
    }

    return response()->json([
        'status'          => 'success',
        'message'         => "Order {$order->order_number} status updated to '{$data['status']}'." .
                             ($movingToShipped ? ' Tracking email sent to customer.' : ''),
        'order_status'    => $order->status,
        'tracking_number' => $order->tracking_number,
    ]);
}

    /** Edit shipping amount, discount amount, and admin notes for the order */
    public function updateMeta(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'shipping_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string|max:2000',
        ]);

        $order->update([
            'shipping_amount' => $data['shipping_amount'] ?? 0,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'notes'           => $data['notes'] ?? null,
        ]);

        $this->recalculateTotals($order);
        $order->refresh();

        return response()->json([
            'status'  => 'success',
            'message' => 'Order details updated.',
            'order'   => [
                'subtotal'         => number_format($order->subtotal, 2),
                'shipping_amount'  => number_format($order->shipping_amount, 2),
                'discount_amount'  => number_format($order->discount_amount, 2),
                'total_amount'     => number_format($order->total_amount, 2),
            ],
        ]);
    }

    /** Update shipping or billing address */
    public function updateAddress(Request $request, Order $order, string $type): JsonResponse
    {
        $data = $request->validate([
            'full_name'     => 'required|string|max:150',
            'phone'         => 'nullable|string|max:20',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city'          => 'required|string|max:100',
            'state'         => 'nullable|string|max:100',
            'postal_code'   => 'nullable|string|max:20',
            'country_id'    => 'nullable|exists:countries,id',
        ]);

        $order->addresses()->updateOrCreate(['type' => $type], $data);

        return response()->json([
            'status'  => 'success',
            'message' => ucfirst($type) . ' address updated.',
        ]);
    }

    /** Search books for the "add item" typeahead */
    public function searchBooks(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));

        $books = Book::query()
            ->when($q !== '', fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->published()
            ->limit(20)
            ->get(['id', 'title', 'cover_image']);

        return response()->json($books->map(fn ($b) => [
            'id'    => $b->id,
            'title' => $b->title,
            'cover' => $b->cover_image_url,
        ]));
    }

    /** Formats + live price for a chosen book, scoped to the order's currency */
    public function bookFormats(Request $request, Book $book): JsonResponse
    {
        $order      = $request->has('order_id') ? Order::find($request->get('order_id')) : null;
        $currencyId = $order->currency_id ?? null;

        $formats = $book->formats()
            ->wherePivot('is_enabled', true)
            ->get()
            ->map(function ($format) use ($book, $currencyId) {
                $price = BookPrice::where('book_id', $book->id)
                    ->where('book_format_id', $format->id)
                    ->when($currencyId, fn ($q) => $q->where('currency_id', $currencyId))
                    ->where('is_active', true)
                    ->first();

                return [
                    'id'        => $format->id,
                    'name'      => $format->name,
                    'price'     => $price ? (float) ($price->sale_price ?? $price->price) : 0,
                    // false = no active BookPrice row exists for this book/format/currency
                    // combo. The frontend must not silently submit 0 in this case — it
                    // should require the admin to type a price before adding.
                    'has_price' => (bool) $price,
                ];
            });

        return response()->json($formats);
    }

    /** Add a book (or bump quantity if it's already on the order) */
    public function addItem(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'book_id'        => 'required|exists:books,id',
            'book_format_id' => 'required|exists:book_formats,id',
            'quantity'       => 'required|integer|min:1|max:999',
            'unit_price'     => 'required|numeric|min:0.01',
        ]);

        $book   = Book::findOrFail($data['book_id']);
        $format = $book->formats()->wherePivot('is_enabled', true)->find($data['book_format_id']);

        abort_if(! $format, 422, 'That format is not available for this book.');

        // Price comes from the admin-confirmed field in the Add Book panel (pre-filled
        // from BookPrice when available). We do NOT silently fall back to 0 here —
        // if no price exists for the order's currency, the frontend forces the admin
        // to type one in rather than adding a free item by accident.
        $unitPrice = round((float) $data['unit_price'], 2);

        $existing = $order->items()
            ->where('book_id', $book->id)
            ->where('book_format_id', $format->id)
            ->first();

        if ($existing) {
            $newQty = $existing->quantity + $data['quantity'];
            $existing->update([
                'quantity'    => $newQty,
                'unit_price'  => $unitPrice,
                'total_price' => $unitPrice * $newQty,
            ]);
        } else {
            OrderItem::create([
                'order_id'       => $order->id,
                'book_id'        => $book->id,
                'book_format_id' => $format->id,
                'title'          => $book->title,
                'format_name'    => $format->name,
                'unit_price'     => $unitPrice,
                'quantity'       => $data['quantity'],
                'total_price'    => $unitPrice * $data['quantity'],
            ]);
        }

        $this->recalculateTotals($order);
        $order->refresh();

        return response()->json([
            'status'  => 'success',
            'message' => "\"{$book->title}\" added to the order.",
            'order'   => [
                'subtotal'     => number_format($order->subtotal, 2),
                'total_amount' => number_format($order->total_amount, 2),
            ],
        ]);
    }

    /** Change an existing item's quantity */
    public function updateItemQty(Request $request, Order $order, OrderItem $item): JsonResponse
    {
        abort_unless($item->order_id === $order->id, 404);

        $data = $request->validate(['quantity' => 'required|integer|min:1|max:999']);

        $item->update([
            'quantity'    => $data['quantity'],
            'total_price' => $item->unit_price * $data['quantity'],
        ]);

        $this->recalculateTotals($order);
        $order->refresh();

        return response()->json([
            'status'  => 'success',
            'message' => 'Quantity updated.',
            'item'    => ['total_price' => number_format($item->total_price, 2)],
            'order'   => [
                'subtotal'     => number_format($order->subtotal, 2),
                'total_amount' => number_format($order->total_amount, 2),
            ],
        ]);
    }

    /** Remove a book from the order */
    public function removeItem(Order $order, OrderItem $item): JsonResponse
    {
        abort_unless($item->order_id === $order->id, 404);
        abort_if($order->items()->count() <= 1, 422, 'An order must have at least one item.');

        $item->delete();
        $this->recalculateTotals($order);
        $order->refresh();

        return response()->json([
            'status'  => 'success',
            'message' => 'Item removed from order.',
            'order'   => [
                'subtotal'     => number_format($order->subtotal, 2),
                'total_amount' => number_format($order->total_amount, 2),
            ],
        ]);
    }

    private function recalculateTotals(Order $order): void
    {
        $subtotal = $order->items()->sum('total_price');

        $order->update([
            'subtotal'     => $subtotal,
            'total_amount' => $subtotal
                            + ($order->shipping_amount ?? 0)
                            + ($order->tax_amount ?? 0)
                            - ($order->discount_amount ?? 0),
        ]);
    }
}