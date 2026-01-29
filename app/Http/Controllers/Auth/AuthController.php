<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember');

        if (!Auth::attempt([
            'USERNAME' => $request->username, 
            'password' => $request->password
        ], $remember)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }

        $request->session()->regenerate();
        
        // Set different session lifetime based on remember me
        if ($remember) {
            // Remember for 30 days
            config(['session.lifetime' => 43200]); // 30 days in minutes
        } else {
            // Short session - 2 hours
            config(['session.lifetime' => 120]);
        }

        return response()->json([
            'message' => 'Logged in successfully',
            'user' => Auth::user()
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        // Return 401 if not authenticated
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        return response()->json(Auth::user());
    }
}