<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Services\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        'payment_method'            => 'required|in:atome,cod', // <-- NEW
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
                'payment_method'  => $data['payment_method'], // <-- dynamic now
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

            return $order->load('items', 'shippingAddress.country', 'currency');
        });

        // ---------- BRANCH: COD vs Atome ----------
        if ($data['payment_method'] === 'cod') {
            Transaction::create([
                'order_id'          => $order->id,
                'gateway'           => 'cod',
                'gateway_intent_id' => null,
                'amount'            => $order->total_amount,
                'currency'          => $order->currency->code ?? 'SGD',
                'status'            => 'pending', // becomes 'captured' when delivered & collected
                'checkout_url'      => null,
                'raw_request'       => null,
            ]);

            // COD orders can move straight to "processing" since no online payment is needed
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


}