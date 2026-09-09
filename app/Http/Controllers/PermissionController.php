<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::with('roles')->orderBy('name')->get()
            ->groupBy(function($perm) {
                return explode(' ', $perm->name)[1] ?? 'other';
            });
        return view('permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('permissions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'module'    => 'required|string|max:100',
            'actions'   => 'required|array|min:1',
            'actions.*' => 'required|string|max:100',
        ], [
            'module.required'   => 'Module name is required.',
            'actions.required'  => 'Please select at least one action.',
            'actions.min'       => 'Please select at least one action.',
        ]);

        $module  = strtolower(trim($request->module));
        $created = [];
        $skipped = [];

        foreach ($request->actions as $action) {
            $action = strtolower(trim($action));
            $name   = $action . ' ' . $module;

            // Check if already exists
            if (Permission::where('name', $name)->exists()) {
                $skipped[] = $name;
                continue;
            }

            Permission::create(['name' => $name, 'guard_name' => 'web']);
            $created[] = $name;
        }

        $message = count($created) . ' permission(s) created successfully!';
        if (count($skipped) > 0) {
            $message .= ' ' . count($skipped) . ' already existed and were skipped.';
        }

        return response()->json([
            'status'   => 'success',
            'message'  => $message . ' 🎉',
            'redirect' => route('permissions.index'),
            'created'  => $created,
            'skipped'  => $skipped,
        ]);
    }

    public function edit(Permission $permission)
{
    // Split "view contacts" → action = "view", module = "contacts"
    $parts  = explode(' ', $permission->name);
    $action = $parts[0] ?? '';
    $module = implode(' ', array_slice($parts, 1)) ?: '';

    return view('permissions.edit', compact('permission', 'action', 'module'));
}

public function update(Request $request, Permission $permission)
{
    $request->validate([
        'module' => 'required|string|max:100',
        'action' => 'required|string|max:100',
    ], [
        'module.required' => 'Module name is required.',
        'action.required' => 'Action name is required.',
    ]);

    $module = strtolower(trim($request->module));
    $action = strtolower(trim($request->action));
    $name   = $action . ' ' . $module;

    // Block renaming into a name that already belongs to a DIFFERENT permission
    $clash = Permission::where('name', $name)
        ->where('id', '!=', $permission->id)
        ->exists();

    if ($clash) {
        return response()->json([
            'status' => 'error',
            'errors' => [
                'module' => ["A permission named \"{$name}\" already exists."],
            ],
        ], 422);
    }

    $permission->update(['name' => $name]);

    return response()->json([
        'status'   => 'success',
        'message'  => 'Permission updated successfully! ✅',
        'redirect' => route('permissions.index'),
    ]);
}

    public function destroy(Permission $permission)
    {
        $permission->roles()->detach();
        $permission->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Permission deleted successfully!',
        ]);
    }
}