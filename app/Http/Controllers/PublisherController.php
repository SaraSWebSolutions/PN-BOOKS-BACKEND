<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PublisherProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PublisherController extends Controller
{
    const UPLOAD_DIR = 'uploads/publishers';

    public function index()
    {
        $publishers = User::role('publisher')->with('publisherProfile')->latest()->get();
        return view('publishers.index', compact('publishers'));
    }

    public function create()
    {
        return view('publishers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                 => 'required|string|max:255',
            'email'                => 'required|email|unique:users,email',
            'phone'                => 'nullable|string|max:20',
            'password'             => 'required|min:6|confirmed',
            'status'               => 'required|in:active,inactive',
            'logo'                 => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'company_name'         => 'required|string|max:255',
            'gst_number'           => 'nullable|string|max:30',
            'pan_number'           => 'nullable|string|max:20',
            'contact_person'       => 'nullable|string|max:150',
            'contact_phone'        => 'nullable|string|max:20',
            'contact_email'        => 'nullable|email|max:150',
            'company_address'      => 'nullable|string',
            'verification_status'  => 'nullable|in:pending,verified,rejected',
            'bank_account_name'    => 'nullable|string|max:150',
            'bank_account_number'  => 'nullable|string|max:50',
            'bank_ifsc'            => 'nullable|string|max:20',
        ]);

        $logoPath = $request->hasFile('logo') ? $this->saveLogo($request->file('logo')) : null;

        // users.status stays as string ('active' / 'inactive') — that column is still an enum
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            'status'   => $request->status,
        ]);

        $user->assignRole('publisher');

        PublisherProfile::create([
            'user_id'             => $user->id,
            'company_name'        => $request->company_name,
            'slug'                => Str::slug($request->company_name) . '-' . $user->id,
            'logo'                => $logoPath,
            'gst_number'          => $request->gst_number,
            'pan_number'          => $request->pan_number,
            'contact_person'      => $request->contact_person,
            'contact_phone'       => $request->contact_phone,
            'contact_email'       => $request->contact_email,
            'company_address'     => $request->company_address,
            'verification_status' => $request->verification_status ?? 'pending',
            'status'              => 1, // FIX: publisher_profiles.status is TINYINT now: 1 = active
            'bank_account_name'   => $request->bank_account_name,
            'bank_account_number' => $request->bank_account_number,
            'bank_ifsc'           => $request->bank_ifsc,
        ]);

        return response()->json([
            'status'   => 'success',
            'message'  => 'Publisher created successfully! 🎉',
            'redirect' => route('publishers.index'),
        ]);
    }

    public function show(User $publisher)
    {
        $publisher->load('publisherProfile');
        return view('publishers.view', compact('publisher'));
    }

    public function edit(User $publisher)
    {
        $publisher->load('publisherProfile');
        return view('publishers.edit', compact('publisher'));
    }

    public function update(Request $request, User $publisher)
    {
        $request->validate([
            'name'                 => 'required|string|max:255',
            'email'                => 'required|email|unique:users,email,' . $publisher->id,
            'phone'                => 'nullable|string|max:20',
            'password'             => 'nullable|min:6|confirmed',
            'status'               => 'required|in:active,inactive',
            'logo'                 => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'company_name'         => 'required|string|max:255',
            'gst_number'           => 'nullable|string|max:30',
            'pan_number'           => 'nullable|string|max:20',
            'contact_person'       => 'nullable|string|max:150',
            'contact_phone'        => 'nullable|string|max:20',
            'contact_email'        => 'nullable|email|max:150',
            'company_address'      => 'nullable|string',
            'verification_status'  => 'nullable|in:pending,verified,rejected',
            'bank_account_name'    => 'nullable|string|max:150',
            'bank_account_number'  => 'nullable|string|max:50',
            'bank_ifsc'            => 'nullable|string|max:20',
            'profile_status'       => 'nullable|boolean', // optional: only if your edit form has a profile-status toggle
        ]);

        $data = [
            'name'   => $request->name,
            'email'  => $request->email,
            'phone'  => $request->phone,
            'status' => $request->status,
        ];

        if ($request->boolean('remove_photo')) {
            $this->deleteLogo($publisher->publisherProfile->logo ?? null);
            $logoPath = null;
        } elseif ($request->hasFile('logo')) {
            $this->deleteLogo($publisher->publisherProfile->logo ?? null);
            $logoPath = $this->saveLogo($request->file('logo'));
        } else {
            $logoPath = $publisher->publisherProfile->logo ?? null;
        }

        $publisher->update($data);

        if ($request->filled('password')) {
            $publisher->update(['password' => Hash::make($request->password)]);
        }

        $profileData = [
            'company_name'         => $request->company_name,
            'slug'                 => Str::slug($request->company_name) . '-' . $publisher->id,
            'logo'                 => $logoPath,
            'gst_number'           => $request->gst_number,
            'pan_number'           => $request->pan_number,
            'contact_person'       => $request->contact_person,
            'contact_phone'        => $request->contact_phone,
            'contact_email'        => $request->contact_email,
            'company_address'      => $request->company_address,
            'verification_status'  => $request->verification_status,
            'bank_account_name'    => $request->bank_account_name,
            'bank_account_number'  => $request->bank_account_number,
            'bank_ifsc'            => $request->bank_ifsc,
        ];

        // Only touch profile status if the edit form sends this field
        if ($request->has('profile_status')) {
            $profileData['status'] = $request->boolean('profile_status') ? 1 : 0;
        }

        $publisher->publisherProfile()->updateOrCreate(
            ['user_id' => $publisher->id],
            $profileData
        );

        return response()->json([
            'status'   => 'success',
            'message'  => 'Publisher updated successfully! ✅',
            'redirect' => route('publishers.index'),
        ]);
    }

    public function destroy(User $publisher)
    {
        $this->deleteLogo($publisher->publisherProfile->logo ?? null);
        $publisher->publisherProfile()->delete();
        $publisher->delete();

        return response()->json(['status' => 'success', 'message' => 'Publisher deleted successfully!']);
    }

    private function saveLogo($file): string
    {
        $uploadPath = base_path(self::UPLOAD_DIR);
        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        $filename = 'pub_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($uploadPath, $filename);

        return self::UPLOAD_DIR . '/' . $filename;
    }

    private function deleteLogo(?string $relativePath): void
    {
        if ($relativePath && File::exists(base_path($relativePath))) {
            File::delete(base_path($relativePath));
        }
    }
}