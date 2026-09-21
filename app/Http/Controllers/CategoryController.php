<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    // Same pattern as AuthorController::UPLOAD_DIR — relative to project root
    const UPLOAD_DIR = 'uploads/categories';

    private function rules(?int $ignoreId = null): array
    {
        return [
            'name_en'        => 'required|string|max:150|unique:categories,name_en,' . ($ignoreId ?? 'NULL'),
            'name_ms'        => 'nullable|string|max:150',
            'description_en' => 'nullable|string|max:1000',
            'description_ms' => 'nullable|string|max:1000',
            'image'          => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active'      => 'nullable|boolean',
        ];
    }

    public function index()
    {
        $categories = Category::withCount('subcategories', 'books')->ordered()->get();

        $stats = [
            'total'    => Category::count(),
            'active'   => Category::active()->count(),
            'inactive' => Category::where('is_active', false)->count(),
        ];

        return view('categories.index', compact('categories', 'stats'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate($this->rules());

        $category = Category::create([
            'name_en'        => trim($request->name_en),
            'name_ms'        => trim($request->name_ms ?? ''),
            'slug'           => Str::slug($request->name_en),
            'description_en' => $request->description_en,
            'description_ms' => $request->description_ms,
            'image'          => $request->hasFile('image') ? $this->saveImage($request->file('image')) : null,
            'is_active'      => $request->boolean('is_active', true),
            'created_by'     => Auth::id(),
            'updated_by'     => Auth::id(),
        ]);

        return response()->json([
            'status'   => 'success',
            'message'  => "Category '{$category->name_en}' created! 🎉",
            'category' => $category,
        ]);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $request->validate($this->rules($category->id));

        $data = [
            'name_en'        => trim($request->name_en),
            'name_ms'        => trim($request->name_ms ?? ''),
            'description_en' => $request->description_en,
            'description_ms' => $request->description_ms,
            'is_active'      => $request->boolean('is_active', $category->is_active),
            'updated_by'     => Auth::id(),
        ];

        if ($request->hasFile('image')) {
            $this->deleteImage($category->image);
            $data['image'] = $this->saveImage($request->file('image'));
        }

        $category->update($data);

        return response()->json([
            'status'   => 'success',
            'message'  => "Category '{$category->name_en}' updated! ✅",
            'category' => $category->fresh(),
        ]);
    }

    public function toggleStatus(Category $category): JsonResponse
    {
        $category->update([
            'is_active'  => ! $category->is_active,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'status'    => 'success',
            'message'   => "'{$category->name}' " . ($category->is_active ? 'activated' : 'deactivated') . '.',
            'is_active' => $category->is_active,
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->subcategories()->exists() || $category->books()->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot delete — category has subcategories or books linked to it.',
            ], 422);
        }

        $name = $category->name;
        $this->deleteImage($category->image);
        $category->delete();

        return response()->json([
            'status'  => 'success',
            'message' => "'{$name}' deleted.",
        ]);
    }

    /* ── Streams category images from project-root uploads/categories (outside public/) ── */
    public function serveImage(string $filename)
    {
        $path = base_path(self::UPLOAD_DIR . '/' . basename($filename));

        if (! File::exists($path) || ! File::isFile($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type'  => File::mimeType($path) ?: 'application/octet-stream',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    // ── Image helpers ────────────────────────────────────────
    private function saveImage($file): string
    {
        $uploadPath = base_path(self::UPLOAD_DIR); // -> D:\...\Pnbooks\uploads\categories
        if (! File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }
        $filename = 'cat_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($uploadPath, $filename);

        return self::UPLOAD_DIR . '/' . $filename; // stored in DB as "uploads/categories/cat_xxx.jpg"
    }

    private function deleteImage(?string $relativePath): void
    {
        if ($relativePath && File::exists(base_path($relativePath))) {
            File::delete(base_path($relativePath));
        }
    }
}