<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuthorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AuthorController extends Controller
{
    const UPLOAD_DIR = 'uploads/authors';

    public function index()
    {
        $authors = User::role('author')->with('authorProfile')->latest()->get();
        return view('authors.index', compact('authors'));
    }

    public function create()
    {
        return view('authors.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                 => 'required|string|max:255',
            'email'                => 'required|email|unique:users,email',
            'phone'                => 'nullable|string|max:20',
            'password'             => 'required|min:6|confirmed',
            'status'               => 'required|in:active,inactive',
            'photo'                => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'pen_name'             => 'nullable|string|max:150',
            'bio'                  => 'nullable|string',
            'website'              => 'nullable|url|max:255',
            'social_links'         => 'nullable|string',
            'verification_status'  => 'nullable|in:pending,verified,rejected',
            'commission_rate'      => 'nullable|numeric|min:0|max:100',
            'bank_account_name'    => 'nullable|string|max:150',
            'bank_account_number'  => 'nullable|string|max:50',
            'bank_ifsc'            => 'nullable|string|max:20',
        ]);

        // photo belongs to author_profiles.profile_photo, NOT users.photo
        $photoPath = $request->hasFile('photo') ? $this->savePhoto($request->file('photo')) : null;

        // users.status stays as string ('active' / 'inactive') — matches enum column
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            'status'   => $request->status,
        ]);

        $user->assignRole('author');

        AuthorProfile::create([
            'user_id'             => $user->id,
            'pen_name'            => $request->pen_name,
            'slug'                => $request->pen_name ? Str::slug($request->pen_name) . '-' . $user->id : null,
            'bio'                 => $request->bio,
            'website'             => $request->website,
            'social_links'        => $request->social_links,
            'profile_photo'       => $photoPath,
            'verification_status' => $request->verification_status ?? 'pending',
            'status'              => 1, // author_profiles.status is TINYINT now: 1 = active
            'commission_rate'     => $request->commission_rate,
            'bank_account_name'   => $request->bank_account_name,
            'bank_account_number' => $request->bank_account_number,
            'bank_ifsc'           => $request->bank_ifsc,
        ]);

        return response()->json([
            'status'   => 'success',
            'message'  => 'Author created successfully! 🎉',
            'redirect' => route('authors.index'),
        ]);
    }

    public function show(User $author)
    {
        $author->load('authorProfile');
        return view('authors.view', compact('author'));
    }

    public function edit(User $author)
    {
        $author->load('authorProfile');
        return view('authors.edit', compact('author'));
    }

    public function update(Request $request, User $author)
    {
        $request->validate([
            'name'                 => 'required|string|max:255',
            'email'                => 'required|email|unique:users,email,' . $author->id,
            'phone'                => 'nullable|string|max:20',
            'password'             => 'nullable|min:6|confirmed',
            'status'               => 'required|in:active,inactive',
            'photo'                => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'pen_name'             => 'nullable|string|max:150',
            'bio'                  => 'nullable|string',
            'website'              => 'nullable|url|max:255',
            'social_links'         => 'nullable|string',
            'verification_status'  => 'nullable|in:pending,verified,rejected',
            'commission_rate'      => 'nullable|numeric|min:0|max:100',
            'bank_account_name'    => 'nullable|string|max:150',
            'bank_account_number'  => 'nullable|string|max:50',
            'bank_ifsc'            => 'nullable|string|max:20',
            'profile_status'       => 'nullable|boolean', // optional: for author_profiles.status toggle in edit form
        ]);

        $data = [
            'name'   => $request->name,
            'email'  => $request->email,
            'phone'  => $request->phone,
            'status' => $request->status,
        ];

        $author->update($data);

        if ($request->filled('password')) {
            $author->update(['password' => Hash::make($request->password)]);
        }

        $profileData = [
            'pen_name'             => $request->pen_name,
            'slug'                 => $request->pen_name ? Str::slug($request->pen_name) . '-' . $author->id : null,
            'bio'                  => $request->bio,
            'website'              => $request->website,
            'social_links'         => $request->social_links,
            'verification_status'  => $request->verification_status,
            'commission_rate'      => $request->commission_rate,
            'bank_account_name'    => $request->bank_account_name,
            'bank_account_number'  => $request->bank_account_number,
            'bank_ifsc'            => $request->bank_ifsc,
        ];

        // Only touch profile status if the edit form actually sends this field
        if ($request->has('profile_status')) {
            $profileData['status'] = $request->boolean('profile_status') ? 1 : 0;
        }

        $existingProfile = $author->authorProfile;

        if ($request->boolean('remove_photo')) {
            $this->deletePhoto($existingProfile?->profile_photo);
            $profileData['profile_photo'] = null;
        } elseif ($request->hasFile('photo')) {
            $this->deletePhoto($existingProfile?->profile_photo);
            $profileData['profile_photo'] = $this->savePhoto($request->file('photo'));
        }

        $author->authorProfile()->updateOrCreate(
            ['user_id' => $author->id],
            $profileData
        );

        return response()->json([
            'status'   => 'success',
            'message'  => 'Author updated successfully! ✅',
            'redirect' => route('authors.index'),
        ]);
    }

    public function destroy(User $author)
    {
        $this->deletePhoto($author->authorProfile?->profile_photo);
        $author->authorProfile()->delete();
        $author->delete();

        return response()->json(['status' => 'success', 'message' => 'Author deleted successfully!']);
    }

    private function savePhoto($file): string
    {
        $uploadPath = base_path(self::UPLOAD_DIR);
        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        $filename = 'auth_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
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