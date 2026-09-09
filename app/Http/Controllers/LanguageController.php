<?php

namespace App\Http\Controllers;

use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LanguageController extends Controller
{
    private function rules(): array
    {
        return [
            'name'        => 'required|string|max:100',
            'native_name' => 'nullable|string|max:100',
            'code'        => 'required|string|max:5',
            'status'      => 'nullable|boolean',
        ];
    }

    public function index()
    {
        $languages = Language::ordered()->get();

        $stats = [
            'total'    => Language::count(),
            'active'   => Language::active()->count(),
            'inactive' => Language::where('status', false)->count(),
        ];

        return view('languages.index', compact('languages', 'stats'));
    }

    // Used by the "Add / Edit Book" form to load languages for the select2 dropdown (AJAX)
    public function activeList(): JsonResponse
    {
        return response()->json(
            Language::active()->ordered()->get(['id', 'name', 'code'])
        );
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(array_merge($this->rules(), [
            'name' => $this->rules()['name'] . '|unique:languages,name',
            'code' => $this->rules()['code'] . '|unique:languages,code',
        ]));

        $language = Language::create([
            'name'        => trim($request->name),
            'native_name' => $request->native_name,
            'code'        => strtolower(trim($request->code)),
            'status'      => $request->boolean('status', true),
        ]);

        return response()->json([
            'status'   => 'success',
            'message'  => "Language '{$language->name}' created! 🎉",
            'language' => $language,
        ]);
    }

    public function update(Request $request, Language $language): JsonResponse
    {
        $request->validate(array_merge($this->rules(), [
            'name' => $this->rules()['name'] . '|unique:languages,name,' . $language->id,
            'code' => $this->rules()['code'] . '|unique:languages,code,' . $language->id,
        ]));

        $language->update([
            'name'        => trim($request->name),
            'native_name' => $request->native_name,
            'code'        => strtolower(trim($request->code)),
            'status'      => $request->boolean('status', $language->status),
        ]);

        return response()->json([
            'status'   => 'success',
            'message'  => "Language '{$language->name}' updated! ✅",
            'language' => $language->fresh(),
        ]);
    }

    public function toggleStatus(Language $language): JsonResponse
    {
        $language->update([
            'status' => ! $language->status,
        ]);

        return response()->json([
            'status'    => 'success',
            'message'   => "'{$language->name}' " . ($language->status ? 'activated' : 'deactivated') . '.',
            'is_active' => $language->status,
        ]);
    }

    public function destroy(Language $language): JsonResponse
    {
        // Safe to remove this "if" once your Book model + book_languages pivot are in place.
        if (class_exists(\App\Models\Book::class) && $language->books()->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot delete — this language is linked to one or more books.',
            ], 422);
        }

        $name = $language->name;
        $language->delete();

        return response()->json([
            'status'  => 'success',
            'message' => "'{$name}' deleted.",
        ]);
    }
}