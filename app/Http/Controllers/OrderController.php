<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $request->validate([
            'status' => 'required|in:' . implode(',', $this->orderStatuses),
        ]);

        $order->update(['status' => $request->status]);

        return response()->json([
            'status'      => 'success',
            'message'     => "Order {$order->order_number} status updated to '{$request->status}'.",
            'order_status'=> $order->status,
        ]);
    }
}