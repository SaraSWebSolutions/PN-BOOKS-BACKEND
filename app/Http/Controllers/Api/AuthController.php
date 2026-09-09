<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /* ───────────── Customer Register ───────────── */
    public function register9926(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'email'       => 'required|email|unique:users,email',
            'phone'       => 'nullable|string|max:20',
            'password'    => 'required|string|min:6|confirmed', // expects password_confirmation
            'country_id'  => 'nullable|integer|exists:countries,id',
            'language_id' => 'nullable|integer|exists:languages,id',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'status'   => 'active',
        ]);

        // assign 'customer' role (Spatie)
        $user->assignRole('customer');

        // create customer profile, storing the chosen country/language if given
        CustomerProfile::create([
            'user_id'     => $user->id,
            'country_id'  => $data['country_id'] ?? null,
            'language_id' => $data['language_id'] ?? null,
        ]);

        $token = $user->createToken('customer-token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Registered successfully',
            'user'    => $user->load('customerProfile.country', 'customerProfile.language'),
            'token'   => $token,
        ], 201);
    }

    public function register(Request $request)
{
    $data = $request->validate(
        [
            'name'        => 'required|string|max:150',
            'email'       => 'required|email|unique:users,email',
            'phone'       => 'nullable|string|max:20',
            'password'    => 'required|string|min:6|confirmed',
            'country_id'  => 'nullable|integer|exists:countries,id',
            'language_id' => 'nullable|integer|exists:languages,id',

            // Terms & Conditions
            'terms_accepted' => 'required|accepted',
        ],
        [
            'name.required' => 'Full name is required.',
            'name.string'   => 'Full name must be a valid text.',
            'name.max'      => 'Full name may not exceed 150 characters.',

            'email.required' => 'Email address is required.',
            'email.email'    => 'Please enter a valid email address.',
            'email.unique'   => 'This email address is already registered.',

            'phone.string' => 'Phone number must be a valid text.',
            'phone.max'    => 'Phone number may not exceed 20 characters.',

            'password.required'  => 'Password is required.',
            'password.min'       => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Password confirmation does not match.',

            'country_id.integer' => 'Please select a valid country.',
            'country_id.exists'  => 'The selected country is invalid.',

            'language_id.integer' => 'Please select a valid language.',
            'language_id.exists'  => 'The selected language is invalid.',

            // Terms
            'terms_accepted.required' => 'Please agree to the Terms of Use and Privacy Policy.',
            'terms_accepted.accepted'  => 'You must agree to the Terms of Use and Privacy Policy to create an account.',
        ]
    );

    $user = User::create([
        'name'     => $data['name'],
        'email'    => $data['email'],
        'phone'    => $data['phone'] ?? null,
        'password' => Hash::make($data['password']),
        'status'   => 'active',
    ]);

    // Assign customer role
    $user->assignRole('customer');

    // Create customer profile
    CustomerProfile::create([
        'user_id'     => $user->id,
        'country_id'  => $data['country_id'] ?? null,
        'language_id' => $data['language_id'] ?? null,
    ]);

    $token = $user->createToken('customer-token')->plainTextToken;

    return response()->json([
        'status'  => 'success',
        'message' => 'Registered successfully',
        'user'    => $user->load(
            'customerProfile.country',
            'customerProfile.language'
        ),
        'token'   => $token,
    ], 201);
}
    /* ───────────── Customer Login ───────────── */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        // only allow customers through this endpoint
        if (! $user->hasRole('customer')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This login is for customers only.',
            ], 403);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Account is not active.',
            ], 403);
        }

        $token = $user->createToken('customer-token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Login successful',
            'user'    => $user->load('customerProfile.country', 'customerProfile.language'),
            'token'   => $token,
        ]);
    }

    /* ───────────── Logout ───────────── */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['status' => 'success', 'message' => 'Logged out']);
    }

    /* ───────────── Get logged-in user ───────────── */
    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'user'   => $request->user()->load('customerProfile.country', 'customerProfile.language'),
        ]);
    }

    /* ───────────── Update Profile ─────────────
     * PUT/POST /api/profile
     * (use POST with _method=PUT in form-data when uploading profile_photo,
     * since browsers/Postman can't multipart a real PUT)
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'                   => 'sometimes|string|max:150',
            'phone'                  => 'nullable|string|max:20',
            'date_of_birth'          => 'nullable|date',
            'gender'                 => 'nullable|in:male,female,other',
            'alternate_phone'        => 'nullable|string|max:20',
            'address_line1'          => 'nullable|string|max:255',
            'address_line2'          => 'nullable|string|max:255',
            'city'                   => 'nullable|string|max:100',
            'state'                  => 'nullable|string|max:100',
            'postal_code'            => 'nullable|string|max:20',
            'country_id'             => 'nullable|integer|exists:countries,id',
            'language_id'            => 'nullable|integer|exists:languages,id',
            'newsletter_subscribed'  => 'nullable|boolean',
            'profile_photo'          => 'nullable|image|max:2048', // 2MB
        ]);

        // update basic user-table fields
        $user->update($request->only(['name', 'phone']));

        // make sure a profile row exists (older accounts may not have one)
        $profile = $user->customerProfile ?? CustomerProfile::create(['user_id' => $user->id]);

        $profileData = collect($data)->except(['name', 'phone', 'profile_photo'])->toArray();

        if ($request->hasFile('profile_photo')) {
            // remove old photo if present
            if ($profile->profile_photo) {
                Storage::disk('public')->delete($profile->profile_photo);
            }
            $profileData['profile_photo'] = $request->file('profile_photo')
                ->store('customer-profiles', 'public');
        }

        $profile->update($profileData);

        return response()->json([
            'status'  => 'success',
            'message' => 'Profile updated successfully',
            'user'    => $user->fresh()->load('customerProfile.country', 'customerProfile.language'),
        ]);
    }
}