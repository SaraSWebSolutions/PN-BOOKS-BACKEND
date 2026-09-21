<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuthorProfile;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;

class SearchApiController extends Controller
{
    public function index(Request $request)
    {
        $query = trim($request->get('q', ''));

        if (strlen($query) < 2) {
            return response()->json([
                'status'     => 'success',
                'query'      => $query,
                'books'      => [],
                'authors'    => [],
                'categories' => [],
            ]);
        }

        $books = Book::published()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('subtitle', 'like', "%{$query}%")
                  ->orWhere('isbn', 'like', "%{$query}%")
                  ->orWhere('short_description', 'like', "%{$query}%");
            })
            ->with(['author', 'category'])
            ->limit(20)
            ->get()
            ->map(function ($book) {
                return [
                    'id'              => $book->id,
                    'title'           => $book->title,
                    'slug'            => $book->slug,
                    'cover_image_url' => $book->cover_image_url,
                    'author'          => $book->author?->pen_name,
                    'category'        => $book->category?->name,
                ];
            });

        $authors = AuthorProfile::active()
            ->where('pen_name', 'like', "%{$query}%")
            ->limit(10)
            ->get()
            ->map(function ($author) {
                return [
                    'id'                => $author->id,
                    'pen_name'          => $author->pen_name,
                    'slug'              => $author->slug,
                    'profile_photo_url' => $author->profile_photo_url,
                ];
            });

        $categories = Category::active()
            ->where(function ($q) use ($query) {
                $q->where('name_en', 'like', "%{$query}%")
                  ->orWhere('name_ms', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($category) {
                return [
                    'id'        => $category->id,
                    'name'      => $category->name,
                    'slug'      => $category->slug,
                    'image_url' => $category->image_url,
                ];
            });

        return response()->json([
            'status'     => 'success',
            'query'      => $query,
            'books'      => $books,
            'authors'    => $authors,
            'categories' => $categories,
        ]);
    }
}