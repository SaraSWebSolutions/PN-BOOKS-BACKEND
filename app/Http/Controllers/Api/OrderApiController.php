<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\Book;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Services\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Mail\OrderPlacedCustomerMail;
use App\Mail\OrderPlacedAdminMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Models\BookFormat; 

class OrderApiController extends Controller
{
    public function __construct(protected PaymentGatewayInterface $gateway)
    {
    }

    /** List the logged-in customer's own orders */
    public function index()
    {
        $orders = Order::with('items')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(15);

        return response()->json(['status' => 'success', 'orders' => $orders]);
    }

    public function show(Order $order)
    {
        abort_if($order->user_id !== Auth::id(), 403);

        $order->load('items', 'addresses', 'transactions');

        return response()->json(['status' => 'success', 'order' => $order]);
    }

    /**
     * Convert the current cart into an order, then create a payment checkout
     * session with Atome (via the injected gateway) and return the redirect URL.
     */
    public function checkoutold(Request $request)
    {
        $data = $request->validate([
            'shipping'                  => 'required|array',
            'shipping.full_name'        => 'required|string|max:150',
            'shipping.phone'            => 'nullable|string|max:20',
            'shipping.address_line1'    => 'required|string|max:255',
            'shipping.address_line2'    => 'nullable|string|max:255',
            'shipping.city'             => 'required|string|max:100',
            'shipping.state'            => 'nullable|string|max:100',
            'shipping.postal_code'      => 'nullable|string|max:20',
            'shipping.country_id'       => 'nullable|exists:countries,id',
            'billing_same_as_shipping'  => 'nullable|boolean',
            'billing'                   => 'nullable|array',
        ]);

        $cart = Cart::where('user_id', Auth::id())->where('status', 'active')->with('items')->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'Your cart is empty.'], 422);
        }

        try {
            $order = DB::transaction(function () use ($cart, $data) {
                $subtotal = $cart->subtotal;

                $order = Order::create([
                    'user_id'         => Auth::id(),
                    'currency_id'     => $cart->items->first()->currency_id,
                    'status'          => 'pending',
                    'payment_status'  => 'pending',
                    'payment_method'  => 'atome',
                    'subtotal'        => $subtotal,
                    'total_amount'    => $subtotal, // add shipping/tax logic here if needed
                    'placed_at'       => now(),
                ]);

                foreach ($cart->items as $item) {
                    OrderItem::create([
                        'order_id'       => $order->id,
                        'book_id'        => $item->book_id,
                        'book_format_id' => $item->book_format_id,
                        'title'          => $item->book->title,
                        'format_name'    => $item->format->name ?? '',
                        'unit_price'     => $item->unit_price,
                        'quantity'       => $item->quantity,
                        'total_price'    => $item->unit_price * $item->quantity,
                    ]);
                }

                OrderAddress::create(array_merge($data['shipping'], [
                    'order_id' => $order->id,
                    'type'     => 'shipping',
                ]));

                $billing = ($data['billing_same_as_shipping'] ?? true) ? $data['shipping'] : ($data['billing'] ?? $data['shipping']);
                OrderAddress::create(array_merge($billing, [
                    'order_id' => $order->id,
                    'type'     => 'billing',
                ]));

                $cart->update(['status' => 'converted']);

                return $order->load('items', 'shippingAddress.country', 'currency');
            });

            $checkout = $this->gateway->createCheckout(
                $order,
                returnUrl: config('app.url') . "/checkout/return?order={$order->order_number}",
                cancelUrl: config('app.url') . "/checkout/cancel?order={$order->order_number}",
            );

            Transaction::create([
                'order_id'          => $order->id,
                'gateway'           => 'atome',
                'gateway_intent_id' => $checkout['intent_id'],
                'amount'            => $order->total_amount,
                'currency'          => $order->currency->code ?? 'SGD',
                'status'            => 'initiated',
                'checkout_url'      => $checkout['checkout_url'],
                'raw_request'       => $checkout['raw'],
            ]);

            return response()->json([
                'status'       => 'success',
                'message'      => 'Order created — redirect the customer to complete payment.',
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
                'checkout_url' => $checkout['checkout_url'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }



 public function checkout(Request $request)
{
    $data = $request->validate([
        'shipping'                  => 'required|array',
        'shipping.full_name'        => 'required|string|max:150',
        'shipping.phone'            => 'nullable|string|max:20',
        'shipping.address_line1'    => 'required|string|max:255',
        'shipping.address_line2'    => 'nullable|string|max:255',
        'shipping.city'             => 'required|string|max:100',
        'shipping.state'            => 'nullable|string|max:100',
        'shipping.postal_code'      => 'nullable|string|max:20',
        'shipping.country_id'       => 'nullable|exists:countries,id',
        'billing_same_as_shipping'  => 'nullable|boolean',
        'billing'                   => 'nullable|array',
        'payment_method'            => 'required|in:atome,cod',
    ]);

    $cart = Cart::where('user_id', Auth::id())->where('status', 'active')->with('items')->first();

    if (! $cart || $cart->items->isEmpty()) {
        return response()->json(['status' => 'error', 'message' => 'Your cart is empty.'], 422);
    }

    try {
        $order = DB::transaction(function () use ($cart, $data) {
            $subtotal = $cart->subtotal;

            $order = Order::create([
                'user_id'         => Auth::id(),
                'currency_id'     => $cart->items->first()->currency_id,
                'status'          => 'pending',
                'payment_status'  => 'pending',
                'payment_method'  => $data['payment_method'],
                'subtotal'        => $subtotal,
                'total_amount'    => $subtotal,
                'placed_at'       => now(),
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id'       => $order->id,
                    'book_id'        => $item->book_id,
                    'book_format_id' => $item->book_format_id,
                    'title'          => $item->book->title,
                    'format_name'    => $item->format->name ?? '',
                    'unit_price'     => $item->unit_price,
                    'quantity'       => $item->quantity,
                    'total_price'    => $item->unit_price * $item->quantity,
                ]);
            }

            OrderAddress::create(array_merge($data['shipping'], [
                'order_id' => $order->id,
                'type'     => 'shipping',
            ]));

            $billing = ($data['billing_same_as_shipping'] ?? true) ? $data['shipping'] : ($data['billing'] ?? $data['shipping']);
            OrderAddress::create(array_merge($billing, [
                'order_id' => $order->id,
                'type'     => 'billing',
            ]));

            $cart->update(['status' => 'converted']);

            // 👇 added 'user' here so the emails below can use $order->user
            return $order->load('items', 'shippingAddress.country', 'currency', 'user');
        });

        // ───── send order emails: customer + admin, both at once ─────
        try {
            Mail::to($order->user->email)->send(new OrderPlacedCustomerMail($order));

            $adminEmails = User::role('admin')->pluck('email')->filter();
            if ($adminEmails->isNotEmpty()) {
                Mail::to($adminEmails->first())
                    ->cc($adminEmails->slice(1))
                    ->send(new OrderPlacedAdminMail($order));
            }
        } catch (\Throwable $e) {
            \Log::error('Order email failed: ' . $e->getMessage());
        }

        // ---------- BRANCH: COD vs Atome ----------
        if ($data['payment_method'] === 'cod') {
            Transaction::create([
                'order_id'          => $order->id,
                'gateway'           => 'cod',
                'gateway_intent_id' => null,
                'amount'            => $order->total_amount,
                'currency'          => $order->currency->code ?? 'SGD',
                'status'            => 'pending',
                'checkout_url'      => null,
                'raw_request'       => null,
            ]);

            $order->update(['status' => 'processing']);

            return response()->json([
                'status'       => 'success',
                'message'      => 'Order placed successfully. Pay cash on delivery.',
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
                'payment_method' => 'cod',
            ]);
        }

        // ---------- Existing Atome flow ----------
        $checkout = $this->gateway->createCheckout(
            $order,
            returnUrl: config('app.url') . "/checkout/return?order={$order->order_number}",
            cancelUrl: config('app.url') . "/checkout/cancel?order={$order->order_number}",
        );

        Transaction::create([
            'order_id'          => $order->id,
            'gateway'           => 'atome',
            'gateway_intent_id' => $checkout['intent_id'],
            'amount'            => $order->total_amount,
            'currency'          => $order->currency->code ?? 'SGD',
            'status'            => 'initiated',
            'checkout_url'      => $checkout['checkout_url'],
            'raw_request'       => $checkout['raw'],
        ]);

        return response()->json([
            'status'       => 'success',
            'message'      => 'Order created — redirect the customer to complete payment.',
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
            'checkout_url' => $checkout['checkout_url'],
            'payment_method' => 'atome',
        ]);
    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
    }
}

/**
 * Mark a COD order's payment as collected (call this when delivery is done
 * and cash has been received). Restrict this route to admin/staff only.
 */
public function markCodAsPaid(Order $order)
{
    abort_if($order->payment_method !== 'cod', 422, 'This order is not a COD order.');

    $transaction = $order->latestTransaction;

    abort_if(! $transaction, 404, 'No transaction found for this order.');

    $transaction->update(['status' => 'captured']);

    $order->update([
        'payment_status' => 'paid',
        'status'         => 'completed',
    ]);

    return response()->json(['status' => 'success', 'message' => 'COD payment marked as received.']);
}


public function myBooks(Request $request)
{
    $bookIds = OrderItem::whereHas('order', function ($q) {
            $q->where('user_id', Auth::id())
              ->where('payment_status', 'paid');
        })
        ->pluck('book_id')
        ->unique()
        ->values();

    $books = Book::with(['author', 'publisher', 'category', 'files'])
        ->whereIn('id', $bookIds)
        ->get()
        ->map(function ($book) {
            return [
                'id'                   => $book->id,
                'title'                => $book->title,
                'slug'                 => $book->slug,
                'cover_image_url'      => $book->cover_image_url,
                'author'               => $book->author,      // full object
                'publisher'            => $book->publisher,   // full object
                'ebook_file_url'       => $book->ebook_file_url,
                'audiobook_sample_url' => $book->audiobook_sample_url,
            ];
        });

    return response()->json([
        'status' => 'success',
        'count'  => $books->count(),
        'books'  => $books,
    ]);
}



public function buyNow(Request $request)
{
    $data = $request->validate([
        'book_id'                   => 'required|exists:books,id',
        'book_format_id'            => 'required|exists:book_formats,id',
        'quantity'                  => 'required|integer|min:1',
 
        'shipping'                  => 'required|array',
        'shipping.full_name'        => 'required|string|max:150',
        'shipping.phone'            => 'nullable|string|max:20',
        'shipping.address_line1'    => 'required|string|max:255',
        'shipping.address_line2'    => 'nullable|string|max:255',
        'shipping.city'             => 'required|string|max:100',
        'shipping.state'            => 'nullable|string|max:100',
        'shipping.postal_code'      => 'nullable|string|max:20',
        'shipping.country_id'       => 'nullable|exists:countries,id',
 
        'billing_same_as_shipping'  => 'nullable|boolean',
        'billing'                   => 'nullable|array',
    ]);
 
    // ── Load the book with its prices so we can find the right row ──
    $book   = Book::with('prices')->findOrFail($data['book_id']);
    $format = BookFormat::findOrFail($data['book_format_id']);
    $qty    = $data['quantity'];
 
    $countryId = $data['shipping']['country_id'] ?? null;
 
    // ── Find the matching BookPrice row for this format + country ──
    $priceRow = $book->prices
        ->where('book_format_id', $format->id)
        ->when($countryId, fn ($rows) => $rows->where('country_id', $countryId))
        ->first();
 


        //  dd($priceRow);
    if (! $priceRow) {
        return response()->json([
            'status'  => 'error',
            'message' => 'This book format is not available for purchase in the selected country.',
        ], 422);
    }
 
    // final_price is the same accessor BookApiController already uses —
    // it already includes sale price (if on sale) + tax.
    $unitPrice  = (float) ($priceRow->final_price ?? $priceRow->price);
    $currencyId = $priceRow->currency_id;
 
    try {
        $order = DB::transaction(function () use ($book, $format, $qty, $data, $unitPrice, $currencyId) {
            $subtotal = round($unitPrice * $qty, 2);
 
            $order = Order::create([
                'user_id'        => Auth::id(),
                'currency_id'    => $currencyId,
                'status'         => 'processing',
                'payment_status' => 'pending',
                'payment_method' => 'cod',
                'subtotal'       => $subtotal,
                'total_amount'   => $subtotal,
                'placed_at'      => now(),
            ]);
 
            OrderItem::create([
                'order_id'       => $order->id,
                'book_id'        => $book->id,
                'book_format_id' => $format->id,
                'title'          => $book->title,
                'format_name'    => $format->name ?? '',
                'unit_price'     => $unitPrice,
                'quantity'       => $qty,
                'total_price'    => $subtotal,
            ]);
 
            OrderAddress::create(array_merge($data['shipping'], [
                'order_id' => $order->id,
                'type'     => 'shipping',
            ]));
 
            $billing = ($data['billing_same_as_shipping'] ?? true)
                ? $data['shipping']
                : ($data['billing'] ?? $data['shipping']);
 
            OrderAddress::create(array_merge($billing, [
                'order_id' => $order->id,
                'type'     => 'billing',
            ]));
 
            return $order->load('items', 'shippingAddress.country', 'currency', 'user');
        });
 
        Transaction::create([
            'order_id'          => $order->id,
            'gateway'           => 'cod',
            'gateway_intent_id' => null,
            'amount'            => $order->total_amount,
            'currency'          => $order->currency->code ?? 'SGD',
            'status'            => 'pending',
            'checkout_url'      => null,
            'raw_request'       => null,
        ]);
 
        try {
            Mail::to($order->user->email)->send(new OrderPlacedCustomerMail($order));
 
            $adminEmails = User::role('admin')->pluck('email')->filter();
            if ($adminEmails->isNotEmpty()) {
                Mail::to($adminEmails->first())
                    ->cc($adminEmails->slice(1))
                    ->send(new OrderPlacedAdminMail($order));
            }
        } catch (\Throwable $e) {
            \Log::error('Buy Now order email failed: ' . $e->getMessage());
        }
 
        return response()->json([
            'status'         => 'success',
            'message'        => 'Order placed successfully. Pay cash on delivery.',
            'order_id'       => $order->id,
            'order_number'   => $order->order_number,
            'payment_method' => 'cod',
            'total_amount'   => $order->total_amount,
        ]);
    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
    }
}




}