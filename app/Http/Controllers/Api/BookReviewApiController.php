<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookReview;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookReviewApiController extends Controller
{
    // GET /api/books/{book}/reviews
  // GET /api/books/{book}/reviews
public function index(Request $request, Book $book)
{
    $perPage = (int) $request->get('per_page', 10);

    $reviews = $book->approvedReviews()
        ->with([
            'user:id,name',
            'user.customerProfile:id,user_id,profile_photo',
        ])
        ->latest()
        ->paginate($perPage);

    // Rating breakdown (5-star to 1-star counts + percentages)
    $total = $book->approvedReviews()->count();
    $breakdown = [];
    for ($star = 5; $star >= 1; $star--) {
        $count = $book->approvedReviews()->where('rating', $star)->count();
        $breakdown[] = [
            'star'       => $star,
            'count'      => $count,
            'percentage' => $total > 0 ? round(($count / $total) * 100) : 0,
        ];
    }

    return response()->json([
        'success' => true,
        'data' => [
            'average_rating'   => $book->average_rating,
            'reviews_count'    => $total,
            'rating_breakdown' => $breakdown,
            'reviews'          => $reviews,
        ],
    ]);
}
    // POST /api/books/{book}/reviews
    public function store(Request $request, Book $book)
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title'  => ['nullable', 'string', 'max:150'],
            'review' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        // check if this user actually purchased the book (for the "verified purchase" badge)
        $isVerifiedPurchase = OrderItem::where('book_id', $book->id)
            ->whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('payment_status', 'paid');
            })
            ->exists();

        // one review per user per book — submitting again updates their existing review
        $review = BookReview::updateOrCreate(
            ['book_id' => $book->id, 'user_id' => $user->id],
            [
                'rating'                => $validated['rating'],
                'title'                 => $validated['title'] ?? null,
                'review'                => $validated['review'] ?? null,
                'is_verified_purchase'  => $isVerifiedPurchase,
                'is_approved'           => true, // flip to false if you add moderation
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'data'    => $review->load('user:id,name'),
        ], 201);
    }

    // POST /api/reviews/{review}/helpful
    public function markHelpful(BookReview $review)
    {
        $review->increment('helpful_count');

        return response()->json([
            'success' => true,
            'data'    => ['helpful_count' => $review->helpful_count],
        ]);
    }
}