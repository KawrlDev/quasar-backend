<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Log an auth activity action (LOGIN, LOGOUT)
     */
    private function logActivity(string $action, string $username, string $changes = ''): void
    {
        ActivityLog::create([
            'performed_by' => $username,
            'action'       => $action,
            'target'       => 'AUTH',
            'changes'      => $changes,
        ]);
    }

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
            config(['session.lifetime' => 43200]); // 30 days in minutes
        } else {
            config(['session.lifetime' => 120]); // 2 hours
        }

        $user = Auth::user();

        $this->logActivity(
            'LOGIN',
            $user->USERNAME,
            'Logged in' . ($remember ? ' with Remember Me' : '')
        );

        return response()->json([
            'message' => 'Logged in successfully',
            'user'    => $user
        ]);
    }

    public function logout(Request $request)
    {
        // Prefer username from request body; fall back to session-based Auth::user()
        $username = $request->input('username') ?? Auth::user()?->USERNAME ?? 'Unknown';
        $this->logActivity('LOGOUT', $username, 'Logged out');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json(Auth::user());
    }
}