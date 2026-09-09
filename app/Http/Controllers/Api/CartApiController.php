<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartApiController extends Controller
{
    protected function currentCart(Request $request): Cart
    {
        return Cart::firstOrCreate(
            ['user_id' => Auth::id(), 'status' => 'active'],
        );
    }

    public function index(Request $request)
    {
        $cart = $this->currentCart($request)->load('items.book', 'items.format');

        return response()->json([
            'status' => 'success',
            'cart'   => [
                'id'       => $cart->id,
                'items'    => $cart->items->map(fn ($i) => [
                    'id'             => $i->id,
                    'book_id'        => $i->book_id,
                    'book_format_id' => $i->book_format_id,
                    'title'          => $i->book->title ?? null,
                    'format'         => $i->format->name ?? null,
                    'cover_image'    => $i->book->cover_image_url ?? null,
                    'unit_price'     => (float) $i->unit_price,
                    'quantity'       => $i->quantity,
                    'line_total'     => $i->line_total,
                ]),
                'subtotal' => $cart->subtotal,
            ],
        ]);
    }

    public function addItem(Request $request)
    {
        $data = $request->validate([
            'book_id'        => 'required|exists:books,id',
            'book_format_id' => 'required|exists:book_formats,id',
            'quantity'       => 'nullable|integer|min:1',
        ]);

        $book = Book::findOrFail($data['book_id']);
        $price = $book->prices()
            ->where('book_format_id', $data['book_format_id'])
            ->where('is_active', true)
            ->first();

        if (! $price) {
            return response()->json(['status' => 'error', 'message' => 'This format has no active price set.'], 422);
        }

        $cart = $this->currentCart($request);

        $item = CartItem::firstOrNew([
            'cart_id'        => $cart->id,
            'book_id'        => $data['book_id'],
            'book_format_id' => $data['book_format_id'],
        ]);

        $item->quantity   = ($item->exists ? $item->quantity : 0) + ($data['quantity'] ?? 1);
        $item->unit_price = $price->sale_price ?? $price->price;
        $item->currency_id = $price->currency_id;
        $item->save();

        return response()->json(['status' => 'success', 'message' => 'Added to cart', 'item_id' => $item->id]);
    }

    public function updateItem(Request $request, CartItem $item)
    {
        $data = $request->validate(['quantity' => 'required|integer|min:1']);

        abort_if($item->cart->user_id !== Auth::id(), 403);

        $item->update(['quantity' => $data['quantity']]);

        return response()->json(['status' => 'success', 'message' => 'Quantity updated']);
    }

    public function removeItem(CartItem $item)
    {
        abort_if($item->cart->user_id !== Auth::id(), 403);

        $item->delete();

        return response()->json(['status' => 'success', 'message' => 'Item removed']);
    }

    public function clear(Request $request)
    {
        $this->currentCart($request)->items()->delete();

        return response()->json(['status' => 'success', 'message' => 'Cart cleared']);
    }
}