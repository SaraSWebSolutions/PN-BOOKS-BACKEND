<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FaqController extends Controller
{
      private function rules(?int $ignoreId = null): array
{
    return [
        'question'   => 'required|string|max:255',
        'answer'     => 'required|string|max:2000',
        'type'       => 'required|in:' . implode(',', array_keys(Faq::TYPES)),
        'sort_order' => 'nullable|integer|min:0',
        'is_active'  => 'nullable|boolean',
    ];
}

    public function index()
    {
        $faqs = Faq::ordered()->get();

        $stats = [
            'total'    => Faq::count(),
            'active'   => Faq::active()->count(),
            'inactive' => Faq::where('is_active', false)->count(),
        ];

        return view('faqs.index', compact('faqs', 'stats'));
    }



public function store(Request $request): JsonResponse
{
    $request->validate($this->rules());

    $faq = Faq::create([
        'question'   => trim($request->question),
        'answer'     => trim($request->answer),
        'type'       => $request->type,
        'sort_order' => $request->sort_order ?? 0,
        'is_active'  => $request->boolean('is_active', true),
        'created_by' => Auth::id(),
        'updated_by' => Auth::id(),
    ]);

    return response()->json([
        'status'  => 'success',
        'message' => 'FAQ added! 🎉',
        'faq'     => $faq,
    ]);
}

public function update(Request $request, Faq $faq): JsonResponse
{
    $request->validate($this->rules($faq->id));

    $faq->update([
        'question'   => trim($request->question),
        'answer'     => trim($request->answer),
        'type'       => $request->type,
        'sort_order' => $request->sort_order ?? $faq->sort_order,
        'is_active'  => $request->boolean('is_active', $faq->is_active),
        'updated_by' => Auth::id(),
    ]);

    return response()->json([
        'status'  => 'success',
        'message' => 'FAQ updated! ✅',
        'faq'     => $faq->fresh(),
    ]);
}

    public function toggleStatus(Faq $faq): JsonResponse
    {
        $faq->update([
            'is_active'  => ! $faq->is_active,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'status'    => 'success',
            'message'   => 'FAQ ' . ($faq->is_active ? 'activated' : 'deactivated') . '.',
            'is_active' => $faq->is_active,
        ]);
    }

    public function destroy(Faq $faq): JsonResponse
    {
        $faq->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'FAQ deleted.',
        ]);
    }
}