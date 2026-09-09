<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'              => 'required|string|max:255',
            'phone'             => 'nullable|string|max:20',
            'photo'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_photo'      => 'nullable|boolean',
            'current_password'  => 'nullable|required_with:new_password|string',
            'new_password'      => 'nullable|min:6|confirmed',
        ]);

        // ── Password change (only runs if new_password was filled) ──
        if ($request->filled('new_password')) {
            if (! Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Current password is incorrect.',
                    'errors'  => ['current_password' => ['Current password is incorrect.']],
                ], 422);
            }

            $user->password = Hash::make($request->new_password);
        }

        $user->name  = $request->name;
        $user->phone = $request->phone;
        $user->save();

        // ── Photo is ONLY managed for author/publisher (their own profile tables).
        //    Admin/employee accounts have no photo column on `users` in this app,
        //    so we simply skip photo handling for anyone who isn't author/publisher. ──
        if ($user->hasRole('author') && $user->authorProfile) {
            if ($request->hasFile('photo')) {
                $this->deleteFile($user->authorProfile->profile_photo);
                $user->authorProfile->update([
                    'profile_photo' => $this->storeFile($request->file('photo'), 'uploads/authors', 'auth'),
                ]);
            } elseif ($request->boolean('remove_photo')) {
                $this->deleteFile($user->authorProfile->profile_photo);
                $user->authorProfile->update(['profile_photo' => null]);
            }
        } elseif ($user->hasRole('publisher') && $user->publisherProfile) {
            if ($request->hasFile('photo')) {
                $this->deleteFile($user->publisherProfile->logo);
                $user->publisherProfile->update([
                    'logo' => $this->storeFile($request->file('photo'), 'uploads/publishers', 'pub'),
                ]);
            } elseif ($request->boolean('remove_photo')) {
                $this->deleteFile($user->publisherProfile->logo);
                $user->publisherProfile->update(['logo' => null]);
            }
        }
        // else: admin/employee — no photo field exists for this account type, nothing to do.

        $user->refresh()->load(['authorProfile', 'publisherProfile']);

        return response()->json([
            'status'     => 'success',
            'message'    => 'Profile updated successfully! ✅',
            'name'       => $user->name,
            'avatar_url' => $user->avatar_url,
        ]);
    }

    private function storeFile($file, string $dir, string $prefix): string
    {
        $uploadPath = base_path($dir);
        if (! File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        $filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($uploadPath, $filename);

        return $dir . '/' . $filename;
    }

    private function deleteFile(?string $relativePath): void
    {
        if ($relativePath && File::exists(base_path($relativePath))) {
            File::delete(base_path($relativePath));
        }
    }
}