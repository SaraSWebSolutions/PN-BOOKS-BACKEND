<?php

namespace App\Http\Controllers;

use App\Models\BookFormat;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BookFormatController extends Controller
{
    private function rules(): array
    {
        return [
            'code'               => 'required|string|max:20',
            'name'               => 'required|string|max:100',
            'icon'               => 'nullable|string|max:50',
            'requires_shipping'  => 'nullable|boolean',
            'status'             => 'nullable|boolean',
        ];
    }

    public function index()
    {
        $bookFormats = BookFormat::ordered()->get();

        $stats = [
            'total'    => BookFormat::count(),
            'active'   => BookFormat::active()->count(),
            'inactive' => BookFormat::where('status', false)->count(),
        ];

        return view('book_formats.index', compact('bookFormats', 'stats'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(array_merge($this->rules(), [
            'code' => $this->rules()['code'] . '|unique:book_formats,code',
            'name' => $this->rules()['name'] . '|unique:book_formats,name',
        ]));

        $bookFormat = BookFormat::create([
            'code'              => strtoupper(trim($request->code)),
            'name'              => trim($request->name),
            'icon'              => trim($request->icon) ?: null,
            'requires_shipping' => $request->boolean('requires_shipping', false),
            'status'            => $request->boolean('status', true),
        ]);

        return response()->json([
            'status'     => 'success',
            'message'    => "Book format '{$bookFormat->name}' created! 🎉",
            'bookFormat' => $bookFormat,
        ]);
    }

    public function update(Request $request, BookFormat $bookFormat): JsonResponse
    {
        $request->validate(array_merge($this->rules(), [
            'code' => $this->rules()['code'] . '|unique:book_formats,code,' . $bookFormat->id,
            'name' => $this->rules()['name'] . '|unique:book_formats,name,' . $bookFormat->id,
        ]));

        $bookFormat->update([
            'code'              => strtoupper(trim($request->code)),
            'name'              => trim($request->name),
            'icon'              => trim($request->icon) ?: null,
            'requires_shipping' => $request->boolean('requires_shipping', $bookFormat->requires_shipping),
            'status'            => $request->boolean('status', $bookFormat->status),
        ]);

        return response()->json([
            'status'     => 'success',
            'message'    => "Book format '{$bookFormat->name}' updated! ✅",
            'bookFormat' => $bookFormat->fresh(),
        ]);
    }

    public function toggleStatus(BookFormat $bookFormat): JsonResponse
    {
        $bookFormat->update([
            'status' => ! $bookFormat->status,
        ]);

        return response()->json([
            'status'    => 'success',
            'message'   => "'{$bookFormat->name}' " . ($bookFormat->status ? 'activated' : 'deactivated') . '.',
            'is_active' => $bookFormat->status,
        ]);
    }

    public function destroy(BookFormat $bookFormat): JsonResponse
    {
        // If linked to books, guard the delete:
        // if ($bookFormat->books()->exists()) {
        //     return response()->json([
        //         'status'  => 'error',
        //         'message' => 'Cannot delete — format is in use.',
        //     ], 422);
        // }

        $name = $bookFormat->name;
        $bookFormat->delete();

        return response()->json([
            'status'  => 'success',
            'message' => "'{$name}' deleted.",
        ]);
    }
}