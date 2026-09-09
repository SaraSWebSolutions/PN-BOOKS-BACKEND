<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SubcategoryController extends Controller
{
    private function rules(?int $ignoreId = null): array
{
    return [
        'category_id'    => 'required|exists:categories,id',
        'name_en'        => 'required|string|max:150',
        'name_ms'        => 'nullable|string|max:150',
        'description_en' => 'nullable|string|max:1000',
        'description_ms' => 'nullable|string|max:1000',
        'is_active'      => 'nullable|boolean',
    ];
}

    public function index()
    {
        $subcategories = Subcategory::with('category')->ordered()->get();
        $categories    = Category::active()->ordered()->get();

        $stats = [
            'total'    => Subcategory::count(),
            'active'   => Subcategory::active()->count(),
            'inactive' => Subcategory::where('is_active', false)->count(),
        ];

        return view('subcategories.index', compact('subcategories', 'categories', 'stats'));
    }

    // Used by the "Add Book" form to load subcategories for a chosen category (AJAX)
    public function byCategory(Category $category): JsonResponse
    {
        return response()->json(
            $category->subcategories()->active()->ordered()->get(['id', 'name'])
        );
    }

   public function store(Request $request): JsonResponse
{
    $request->validate(array_merge($this->rules(), [
        'name_en' => $this->rules()['name_en'] .
            '|unique:subcategories,name_en,NULL,id,category_id,' . $request->category_id,
    ]));

    $sub = Subcategory::create([
        'category_id'    => $request->category_id,
        'name_en'        => trim($request->name_en),
        'name_ms'        => trim($request->name_ms ?? ''),
        'slug'           => Str::slug($request->name_en),
        'description_en' => $request->description_en,
        'description_ms' => $request->description_ms,
        'is_active'      => $request->boolean('is_active', true),
        'created_by'     => Auth::id(),
        'updated_by'     => Auth::id(),
    ]);

    return response()->json([
        'status'      => 'success',
        'message'     => "Subcategory '{$sub->name_en}' created! 🎉",
        'subcategory' => $sub->load('category'),
    ]);
}

public function update(Request $request, Subcategory $subcategory): JsonResponse
{
    $request->validate(array_merge($this->rules(), [
        'name_en' => $this->rules()['name_en'] .
            '|unique:subcategories,name_en,' . $subcategory->id . ',id,category_id,' . $request->category_id,
    ]));

    $subcategory->update([
        'category_id'    => $request->category_id,
        'name_en'        => trim($request->name_en),
        'name_ms'        => trim($request->name_ms ?? ''),
        'description_en' => $request->description_en,
        'description_ms' => $request->description_ms,
        'is_active'      => $request->boolean('is_active', $subcategory->is_active),
        'updated_by'     => Auth::id(),
    ]);

    return response()->json([
        'status'      => 'success',
        'message'     => "Subcategory '{$subcategory->name_en}' updated! ✅",
        'subcategory' => $subcategory->fresh()->load('category'),
    ]);
}

    public function toggleStatus(Subcategory $subcategory): JsonResponse
    {
        $subcategory->update([
            'is_active'  => ! $subcategory->is_active,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'status'    => 'success',
            'message'   => "'{$subcategory->name}' " . ($subcategory->is_active ? 'activated' : 'deactivated') . '.',
            'is_active' => $subcategory->is_active,
        ]);
    }

    public function destroy(Subcategory $subcategory): JsonResponse
    {
        if ($subcategory->books()->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot delete — subcategory has books linked to it.',
            ], 422);
        }

        $name = $subcategory->name;
        $subcategory->delete();

        return response()->json([
            'status'  => 'success',
            'message' => "'{$name}' deleted.",
        ]);
    }
}
