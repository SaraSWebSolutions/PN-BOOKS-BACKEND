<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistApiController extends Controller
{
    // GET /api/wishlist
  public function index(Request $request)
{
    $wishlists = Wishlist::with(['book' => function ($q) {
            $q->select('id', 'title', 'slug', 'cover_image', 'author_id', 'category_id')
              ->with('author:id,pen_name'); // ✅ fixed column name
        }])
        ->where('user_id', $request->user()->id)
        ->latest()
        ->paginate($request->get('per_page', 15));

    return response()->json([
        'status' => true,
        'data'   => $wishlists,
    ]);
}

    // POST /api/wishlist/toggle  { "book_id": 5 }
    public function toggle(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|integer|exists:books,id',
        ]);

        $userId = $request->user()->id;

        $existing = Wishlist::where('user_id', $userId)
            ->where('book_id', $validated['book_id'])
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'status'     => true,
                'wishlisted' => false,
                'message'    => 'Removed from wishlist',
            ]);
        }

        Wishlist::create([
            'user_id' => $userId,
            'book_id' => $validated['book_id'],
        ]);

        return response()->json([
            'status'     => true,
            'wishlisted' => true,
            'message'    => 'Added to wishlist',
        ]);
    }

    // GET /api/wishlist/check/{book}
    public function check(Request $request, Book $book)
    {
        $liked = Wishlist::where('user_id', $request->user()->id)
            ->where('book_id', $book->id)
            ->exists();

        return response()->json([
            'status'     => true,
            'wishlisted' => $liked,
        ]);
    }

    // DELETE /api/wishlist/{book}
    public function destroy(Request $request, Book $book)
    {
        $deleted = Wishlist::where('user_id', $request->user()->id)
            ->where('book_id', $book->id)
            ->delete();

        return response()->json([
            'status'  => (bool) $deleted,
            'message' => $deleted ? 'Removed from wishlist' : 'Not found in wishlist',
        ]);
    }
}