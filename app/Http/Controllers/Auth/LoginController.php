<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    // Show login page
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    // ✅ AJAX Login Handler
    public function login(Request $request)
    {
        // Step 1: Validate
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ], [
            'email.required'    => 'Email address is required.',
            'email.email'       => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
            'password.min'      => 'Password must be at least 6 characters.',
        ]);

        // Step 2: Return validation errors as JSON
        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'type'    => 'validation',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Step 3: Attempt login
        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return response()->json([
                'status'   => 'success',
                'message'  => 'Welcome back, ' . Auth::user()->name . '! 👋',
                'redirect' => route('dashboard'),
            ]);
        }

        // Step 4: Wrong credentials
        return response()->json([
            'status'  => 'error',
            'type'    => 'credentials',
            'message' => 'Invalid email or password. Please try again.',
        ], 401);
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been logged out successfully.');
    }
}