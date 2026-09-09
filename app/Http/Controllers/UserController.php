<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    const UPLOAD_DIR = 'uploads/employees';

    public function index()
    {
        $users = User::with('roles')->latest()->get();
        $roles = Role::all();
        return view('users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'nullable|email|unique:users,email',
            'phone'           => 'nullable|string|max:20',
            'employee_code'   => 'nullable|string|max:50|unique:users,employee_code',
            'employee_id'     => 'nullable|string|max:50|unique:users,employee_id',
            'photo'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'password'        => 'required|min:6|confirmed',
            'role'            => 'required|exists:roles,name',
            'status'          => 'required|in:active,inactive',
            'date_of_birth'   => 'nullable|date|before:today',
            'date_of_joining' => 'nullable|date',
            'agent_no'        => 'nullable|string|max:50',
        ]);

        $photoPath = $request->hasFile('photo') ? $this->savePhoto($request->file('photo')) : null;

        $user = User::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'phone'           => $request->phone,
            'employee_code'   => $request->employee_code ?: null,
            'employee_id'     => $request->employee_id ?: null,
            'photo'           => $photoPath,
            'password'        => Hash::make($request->password),
            'status'          => $request->status,
            'date_of_birth'   => $request->date_of_birth ?: null,
            'agent_no'        => $request->agent_no ?: null,
            'date_of_joining' => $request->date_of_joining ?: null,
        ]);

        $user->assignRole($request->role);

        return response()->json([
            'status'   => 'success',
            'message'  => 'User created successfully! 🎉',
            'redirect' => route('users.index'),
        ]);
    }

    public function show(User $user)
    {
        $user->load('roles');
        return view('users.view', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'nullable|email|unique:users,email,' . $user->id,
            'phone'           => 'nullable|string|max:20',
            'employee_code'   => 'nullable|string|max:50|unique:users,employee_code,' . $user->id,
            'employee_id'     => 'nullable|string|max:50|unique:users,employee_id,' . $user->id,
            'photo'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'password'        => 'nullable|min:6|confirmed',
            'role'            => 'required|exists:roles,name',
            'status'          => 'required|in:active,inactive',
            'date_of_birth'   => 'nullable|date|before:today',
            'date_of_joining' => 'nullable|date',
            'agent_no'        => 'nullable|string|max:50',
        ]);

        $data = [
            'name'            => $request->name,
            'email'           => $request->email,
            'phone'           => $request->phone,
            'employee_code'   => $request->employee_code ?: null,
            'employee_id'     => $request->employee_id ?: null,
            'status'          => $request->status,
            'date_of_birth'   => $request->date_of_birth ?: null,
            'date_of_joining' => $request->date_of_joining ?: null,
            'agent_no'        => $request->agent_no ?: null,
        ];

        if ($request->boolean('remove_photo')) {
            $this->deletePhoto($user->photo);
            $data['photo'] = null;
        } elseif ($request->hasFile('photo')) {
            $this->deletePhoto($user->photo);
            $data['photo'] = $this->savePhoto($request->file('photo'));
        }

        $user->update($data);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->syncRoles($request->role);

        return response()->json([
            'status'   => 'success',
            'message'  => 'User updated successfully! ✅',
            'redirect' => route('users.index'),
        ]);
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['status' => 'error', 'message' => 'You cannot delete yourself!'], 403);
        }
        $this->deletePhoto($user->photo);
        $user->delete();
        return response()->json(['status' => 'success', 'message' => 'User deleted successfully!']);
    }

    public function checkEmployeeCode(Request $request)
    {
        $code      = trim($request->query('employee_code', ''));
        $excludeId = $request->query('exclude_id');

        if ($code === '') {
            return response()->json(['available' => true]);
        }

        $exists = User::where('employee_code', $code)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        return response()->json(['available' => !$exists]);
    }

    private function savePhoto($file): string
    {
        $uploadPath = base_path(self::UPLOAD_DIR);
        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        $filename = 'emp_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($uploadPath, $filename);

        return self::UPLOAD_DIR . '/' . $filename;
    }

    private function deletePhoto(?string $relativePath): void
    {
        if ($relativePath && File::exists(base_path($relativePath))) {
            File::delete(base_path($relativePath));
        }
    }
}