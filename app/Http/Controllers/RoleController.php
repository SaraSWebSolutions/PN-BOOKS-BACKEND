<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    // ✅ Index
    public function index()
    {
        $roles = Role::with('permissions')->latest()->get();
        return view('roles.index', compact('roles'));
    }

    // ✅ Create
    public function create()
    {
        $permissions = Permission::orderBy('name')->get()->groupBy(function($perm) {
            return explode(' ', $perm->name)[1] ?? 'other';
        });
        return view('roles.create', compact('permissions'));
    }

    // ✅ Store
    public function store(Request $request)
{
    $request->validate([
        'name'          => 'required|string|unique:roles,name',
        'permissions'   => 'nullable|array',
        'permissions.*' => 'exists:permissions,id', // ✅ validate by ID
    ]);

    $role = Role::create(['name' => strtolower($request->name), 'guard_name' => 'web']);

    if ($request->permissions) {
        // ✅ Convert IDs to permission names
        $permissionNames = Permission::whereIn('id', $request->permissions)->pluck('name')->toArray();
        $role->syncPermissions($permissionNames);
    }

    return response()->json([
        'status'   => 'success',
        'message'  => 'Role created successfully! 🎉',
        'redirect' => route('roles.index'),
    ]);
}

    // ✅ Show
    public function show(Role $role)
    {
        $role->load('permissions');
        $users = \App\Models\User::role($role->name)->get();
        return view('roles.view', compact('role', 'users'));
    }

    // ✅ Edit
    public function edit(Role $role)
    {
        $role->load('permissions');
        $permissions = Permission::orderBy('name')->get()->groupBy(function($perm) {
            return explode(' ', $perm->name)[1] ?? 'other';
        });
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    // ✅ Update
   public function update(Request $request, Role $role)
{
    $request->validate([
        'name'          => 'required|string|unique:roles,name,' . $role->id,
        'permissions'   => 'nullable|array',
        'permissions.*' => 'exists:permissions,id', // ✅ validate by ID
    ]);

    $role->update(['name' => strtolower($request->name)]);

    // ✅ Convert IDs to permission names
    $permissionNames = [];
    if ($request->permissions) {
        $permissionNames = Permission::whereIn('id', $request->permissions)->pluck('name')->toArray();
    }
    $role->syncPermissions($permissionNames);

    return response()->json([
        'status'   => 'success',
        'message'  => 'Role updated successfully! ✅',
        'redirect' => route('roles.index'),
    ]);
}

    // ✅ Delete
    public function destroy(Role $role)
    {
        $protected = ['admin', 'owner', 'manager', 'cashier'];
        
        if (in_array($role->name, $protected)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot delete system default role!',
            ], 403);
        }

        if ($role->users()->count() > 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot delete role with assigned users!',
            ], 403);
        }

        $role->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Role deleted successfully!',
        ]);
    }
}