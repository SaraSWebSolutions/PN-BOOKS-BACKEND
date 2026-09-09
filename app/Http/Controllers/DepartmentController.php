<?php
// app/Http/Controllers/DepartmentController.php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    // ── Validation rules ──────────────────────────────────────

    private function rules(?int $ignoreId = null): array
    {
        return [
            'name'        => 'required|string|max:100|unique:departments,name,' . ($ignoreId ?? 'NULL'),
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'nullable|boolean',
        ];
    }

    // ── Index ─────────────────────────────────────────────────

    public function index()
    {
        $departments = Department::withTrashed()->ordered()->get();

        $stats = [
            'total'    => Department::count(),
            'active'   => Department::active()->count(),
            'inactive' => Department::where('is_active', false)->count(),
        ];

        return view('departments.index', compact('departments', 'stats'));
    }

    // ── Store ─────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $request->validate($this->rules());

        $dept = Department::create([
            'name'        => trim($request->name),
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', true),
            'created_by'  => Auth::id(),
            'updated_by'  => Auth::id(),
        ]);

        return response()->json([
            'status'     => 'success',
            'message'    => "Department '{$dept->name}' created! 🎉",
            'department' => $dept,
        ]);
    }

    // ── Update ────────────────────────────────────────────────

    public function update(Request $request, Department $department): JsonResponse
    {
        $request->validate($this->rules($department->id));

        $department->update([
            'name'        => trim($request->name),
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', $department->is_active),
            'updated_by'  => Auth::id(),
        ]);

        return response()->json([
            'status'     => 'success',
            'message'    => "Department '{$department->name}' updated! ✅",
            'department' => $department->fresh(),
        ]);
    }

    // ── Toggle ────────────────────────────────────────────────

    public function toggleStatus(Department $department): JsonResponse
    {
        $department->update([
            'is_active'  => ! $department->is_active,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'status'    => 'success',
            'message'   => "'{$department->name}' " . ($department->is_active ? 'activated' : 'deactivated') . '.',
            'is_active' => $department->is_active,
        ]);
    }

    // ── Destroy ───────────────────────────────────────────────

    public function destroy(Department $department): JsonResponse
    {
        $name = $department->name;
        $department->delete();

        return response()->json([
            'status'  => 'success',
            'message' => "'{$name}' deleted.",
        ]);
    }
}