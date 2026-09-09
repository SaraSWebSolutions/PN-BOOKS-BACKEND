<?php

namespace App\Http\Controllers;

use App\Models\Series;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SeriesController extends Controller
{
    private function rules(?int $ignoreId = null): array
    {
        return [
            'name'         => 'required|string|max:150|unique:series,name,' . ($ignoreId ?? 'NULL'),
            'description'  => 'nullable|string|max:1000',
            'publisher_id' => 'nullable|exists:users,id',
            'cover_image'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active'    => 'nullable|boolean',
        ];
    }

    public function index()
    {
        $series = Series::with('publisher')->withCount('books')->ordered()->get();

        // Only users assigned the "publisher" role (spatie/permission)
        $publishers = User::role('publisher')->orderBy('name')->get(['id', 'name']);

        $stats = [
            'total'    => Series::count(),
            'active'   => Series::active()->count(),
            'inactive' => Series::where('is_active', false)->count(),
        ];

        return view('series.index', compact('series', 'publishers', 'stats'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate($this->rules());

        $series = Series::create([
            'name'         => trim($request->name),
            'slug'         => Str::slug($request->name),
            'description'  => $request->description,
            'publisher_id' => $request->publisher_id ?: null,
            'cover_image'  => $request->hasFile('cover_image') ? $this->saveImage($request->file('cover_image')) : null,
            'is_active'    => $request->boolean('is_active', true),
            'created_by'   => Auth::id(),
            'updated_by'   => Auth::id(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => "Series '{$series->name}' created! 🎉",
            'series'  => $series->load('publisher'),
        ]);
    }

    public function update(Request $request, Series $series): JsonResponse
    {
        $request->validate($this->rules($series->id));

        $data = [
            'name'         => trim($request->name),
            'description'  => $request->description,
            'publisher_id' => $request->publisher_id ?: null,
            'is_active'    => $request->boolean('is_active', $series->is_active),
            'updated_by'   => Auth::id(),
        ];

        if ($request->hasFile('cover_image')) {
            $this->deleteImage($series->cover_image);
            $data['cover_image'] = $this->saveImage($request->file('cover_image'));
        }

        $series->update($data);

        return response()->json([
            'status'  => 'success',
            'message' => "Series '{$series->name}' updated! ✅",
            'series'  => $series->fresh()->load('publisher'),
        ]);
    }

    public function toggleStatus(Series $series): JsonResponse
    {
        $series->update([
            'is_active'  => ! $series->is_active,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'status'    => 'success',
            'message'   => "'{$series->name}' " . ($series->is_active ? 'activated' : 'deactivated') . '.',
            'is_active' => $series->is_active,
        ]);
    }

    public function destroy(Series $series): JsonResponse
    {
        if ($series->books()->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot delete — series has books linked to it.',
            ], 422);
        }

        $name = $series->name;
        $series->delete();

        return response()->json([
            'status'  => 'success',
            'message' => "'{$name}' deleted.",
        ]);
    }

    private function saveImage($file): string
    {
        $uploadPath = public_path('uploads/series');
        if (! File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }
        $filename = 'series_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($uploadPath, $filename);

        return 'uploads/series/' . $filename;
    }

    private function deleteImage(?string $relativePath): void
    {
        if ($relativePath && File::exists(public_path($relativePath))) {
            File::delete(public_path($relativePath));
        }
    }
}
