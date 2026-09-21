<?php

namespace App\Http\Controllers;

use App\Models\SupportSubject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SupportSubjectController extends Controller
{
    private function rules(): array
    {
        return [
            'name'         => 'required|string|max:150',
            'notify_email' => 'nullable|email|max:150',
            'sort_order'   => 'nullable|integer|min:0',
            'is_active'    => 'nullable|boolean',
        ];
    }

    public function index()
    {
        $subjects = SupportSubject::ordered()->get();
        return view('support-subjects.index', compact('subjects'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate($this->rules());

        $subject = SupportSubject::create([
            'name'         => trim($request->name),
            'notify_email' => $request->notify_email,
            'sort_order'   => $request->sort_order ?? 0,
            'is_active'    => $request->boolean('is_active', true),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Subject added! 🎉', 'subject' => $subject]);
    }

    public function update(Request $request, SupportSubject $support_subject): JsonResponse
    {
        $request->validate($this->rules());

        $support_subject->update([
            'name'         => trim($request->name),
            'notify_email' => $request->notify_email,
            'sort_order'   => $request->sort_order ?? $support_subject->sort_order,
            'is_active'    => $request->boolean('is_active', $support_subject->is_active),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Subject updated! ✅', 'subject' => $support_subject->fresh()]);
    }

    public function toggleStatus(SupportSubject $support_subject): JsonResponse
    {
        $support_subject->update(['is_active' => ! $support_subject->is_active]);

        return response()->json([
            'status'    => 'success',
            'message'   => 'Subject ' . ($support_subject->is_active ? 'activated' : 'deactivated') . '.',
            'is_active' => $support_subject->is_active,
        ]);
    }

    public function destroy(SupportSubject $support_subject): JsonResponse
    {
        $support_subject->delete();
        return response()->json(['status' => 'success', 'message' => 'Subject deleted.']);
    }
}