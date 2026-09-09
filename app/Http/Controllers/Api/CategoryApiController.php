<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryApiController extends Controller
{
    /* ─────────────────────────────────────────────────────────
       ✅ Public: List all active categories (the cards you see
       on Home / Physical Book page — Children's Books, Audio
       Books, eBooks, Religious, Fiction, Non-Fiction, Education)
    ───────────────────────────────────────────────────────── */
    public function index()
    {
        $categories = Category::active()
            ->ordered()
            ->withCount(['books' => function ($q) {
                $q->where('status', 'published')->where('show_in_store', true);
            }])
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $categories,
        ]);
    }

    /* ─────────────────────────────────────────────────────────
       ✅ Public: Single category detail (name, description, image)
    ───────────────────────────────────────────────────────── */
    public function show(Category $category)
    {
        if (! $category->status) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Category not found.',
            ], 404);
        }

        $category->loadCount(['books' => function ($q) {
            $q->where('status', 'published')->where('show_in_store', true);
        }]);

        return response()->json([
            'status' => 'success',
            'data'   => $category,
        ]);
    }

    /* ─────────────────────────────────────────────────────────
       ✅ Public: Subcategories under a category (for filter dropdowns)
    ───────────────────────────────────────────────────────── */
    public function subcategories(Category $category)
    {
        $subcategories = $category->subcategories()
            ->active()
            ->ordered()
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $subcategories,
        ]);
    }
}