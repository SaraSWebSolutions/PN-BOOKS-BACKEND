<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Mail\OtpMail;

class AuthController extends Controller
{
    const UPLOAD_DIR = 'uploads/customers';
    /* ───────────── Customer Register ───────────── */
   

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
            'terms_accepted' => 'required|accepted',
        ],
        [
            'name.required' => 'Full name is required.',
            'email.required' => 'Email address is required.',
            'email.email'    => 'Please enter a valid email address.',
            'email.unique'   => 'This email address is already registered.',
            'password.required'  => 'Password is required.',
            'password.min'       => 'Password must be at least 6 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'terms_accepted.required' => 'Please agree to the Terms of Use and Privacy Policy.',
            'terms_accepted.accepted' => 'You must agree to the Terms of Use and Privacy Policy to create an account.',
        ]
    );

    $otp = (string) random_int(100000, 999999);

    $user = User::create([
        'name'           => $data['name'],
        'email'          => $data['email'],
        'phone'          => $data['phone'] ?? null,
        'password'       => Hash::make($data['password']),
        'status'         => 'inactive', // not active until OTP verified
        'otp'            => Hash::make($otp),
        'otp_expires_at' => now()->addMinutes(10),
    ]);

    $user->assignRole('customer');

    CustomerProfile::create([
        'user_id'     => $user->id,
        'country_id'  => $data['country_id'] ?? null,
        'language_id' => $data['language_id'] ?? null,
    ]);

    Mail::to($user->email)->send(new OtpMail($otp, $user->name));

    return response()->json([
        'status'  => 'success',
        'message' => 'Registered successfully. Please verify the OTP sent to your email.',
        'email'   => $user->email,
    ], 201);
}

public function verifyOtp(Request $request)
{


    $data = $request->validate([
        'email' => 'required|email|exists:users,email',
        'otp'   => 'required|string',
    ]);

    $user = User::where('email', $data['email'])->first();

    if (! $user->otp || ! Hash::check($data['otp'], $user->otp)) {
        throw ValidationException::withMessages([
            'otp' => ['Invalid OTP code.'],
        ]);
    }

    if (now()->greaterThan($user->otp_expires_at)) {
        return response()->json([
            'status'  => 'error',
            'message' => 'This OTP has expired. Please request a new one.',
        ], 422);
    }

    $user->update([
        'email_verified_at' => now(),
        'status'             => 'active',
        'otp'                => null,
        'otp_expires_at'     => null,
    ]);

    $token = $user->createToken('customer-token')->plainTextToken;

    return response()->json([
        'status'  => 'success',
        'message' => 'Email verified successfully',
        'user'    => $user->fresh()->load('customerProfile.country', 'customerProfile.language'),
        'token'   => $token,
    ]);
}


public function resendOtp(Request $request)
{
    $data = $request->validate([
        'email' => 'required|email|exists:users,email',
    ]);

    $user = User::where('email', $data['email'])->first();

    if ($user->email_verified_at) {
        return response()->json([
            'status'  => 'error',
            'message' => 'This account is already verified.',
        ], 422);
    }

    $otp = (string) random_int(100000, 999999);

    $user->update([
        'otp'            => Hash::make($otp),
        'otp_expires_at' => now()->addMinutes(10),
    ]);

    Mail::to($user->email)->send(new OtpMail($otp, $user->name));

    return response()->json([
        'status'  => 'success',
        'message' => 'A new OTP has been sent to your email.',
    ]);
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

    if (! $user->hasRole('customer')) {
        return response()->json([
            'status'  => 'error',
            'message' => 'This login is for customers only.',
        ], 403);
    }

    if (! $user->email_verified_at) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Please verify your email before logging in.',
            'email'   => $user->email,
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
    $user->update(collect($data)->only(['name', 'phone'])->toArray());

    // make sure a profile row exists (older accounts may not have one)
    $profile = $user->customerProfile ?? CustomerProfile::create(['user_id' => $user->id]);

    $profileData = collect($data)->except(['name', 'phone', 'profile_photo'])->toArray();

    // cast newsletter_subscribed properly (form-data sends it as a string "true"/"false")
    if (array_key_exists('newsletter_subscribed', $profileData)) {
        $profileData['newsletter_subscribed'] = $request->boolean('newsletter_subscribed');
    }

    if ($request->hasFile('profile_photo')) {
        // remove old photo if present
        $this->deletePhoto($profile->profile_photo);
        $profileData['profile_photo'] = $this->savePhoto($request->file('profile_photo'));
    }

    $profile->update($profileData);

    return response()->json([
        'status'  => 'success',
        'message' => 'Profile updated successfully',
        'user'    => $user->fresh()->load('customerProfile.country', 'customerProfile.language'),
    ]);
}

private function savePhoto($file): string
{
    $uploadPath = base_path(self::UPLOAD_DIR);
    if (!File::exists($uploadPath)) {
        File::makeDirectory($uploadPath, 0755, true);
    }

    $filename = 'cust_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
    $file->move($uploadPath, $filename);

    return self::UPLOAD_DIR . '/' . $filename;
}

private function deletePhoto(?string $relativePath): void
{
    if ($relativePath && File::exists(base_path($relativePath))) {
        File::delete(base_path($relativePath));
    }
}

    /* ───────────── Forgot Password ───────────── */
public function forgotPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
    ], [
        'email.exists' => 'No account found with this email.',
    ]);

    $token = Str::random(6); // short numeric-ish code is easier for mobile UX; use 60 for a link-based flow

    DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $request->email],
        ['token' => Hash::make($token), 'created_at' => now()]
    );

    Mail::to($request->email)->send(new ResetPasswordMail($token, $request->email));

    return response()->json([
        'status'  => 'success',
        'message' => 'Password reset code sent to your email',
    ]);
}

/* ───────────── Reset Password ───────────── */
public function resetPassword(Request $request)
{
   
    $data = $request->validate([
        'email'    => 'required|email|exists:users,email',
        'token'    => 'required|string',
        'password' => 'required|string|min:6|confirmed',
    ]);

    $record = DB::table('password_reset_tokens')
        ->where('email', $data['email'])
        ->first();

    if (! $record || ! Hash::check($data['token'], $record->token)) {
        throw ValidationException::withMessages([
            'token' => ['Invalid or expired code.'],
        ]);
    }

    if (now()->diffInMinutes($record->created_at) > 60) {
        return response()->json([
            'status'  => 'error',
            'message' => 'This code has expired. Please request a new one.',
        ], 422);
    }

    $user = User::where('email', $data['email'])->first();
    $user->update(['password' => Hash::make($data['password'])]);

    DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

    return response()->json([
        'status'  => 'success',
        'message' => 'Password reset successfully',
    ]);
}

/* ───────────── Change Password (logged-in user) ───────────── */
public function changePassword(Request $request)
{
    $data = $request->validate([
        'current_password' => 'required|string',
        'new_password'      => 'required|string|min:6|confirmed', // expects new_password_confirmation
    ], [
        'current_password.required' => 'Current password is required.',
        'new_password.required'     => 'New password is required.',
        'new_password.min'          => 'New password must be at least 6 characters.',
        'new_password.confirmed'    => 'New password confirmation does not match.',
    ]);

    $user = $request->user();

    if (! Hash::check($data['current_password'], $user->password)) {
        throw ValidationException::withMessages([
            'current_password' => ['Current password is incorrect.'],
        ]);
    }

    $user->update(['password' => Hash::make($data['new_password'])]);

    return response()->json([
        'status'  => 'success',
        'message' => 'Password changed successfully',
    ]);
}
}