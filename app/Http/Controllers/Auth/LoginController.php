<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController
{
    public function showLogin()
    {
        // ✅ If already logged in → redirect directly
        if (auth()->check()) {
            return $this->redirectToPanel(auth()->user());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        // ✅ VALIDATION
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // ✅ ATTEMPT LOGIN
        if (!Auth::attempt(
            $request->only('email', 'password'),
            $request->boolean('remember')
        )) {
            return back()->withErrors([
                'email' => 'Invalid credentials',
            ]);
        }

        // ✅ REGENERATE SESSION (security)
        $request->session()->regenerate();

        // ✅ REDIRECT USING SINGLE METHOD
        return $this->redirectToPanel(Auth::user());
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * ✅ CENTRALIZED ROLE-BASED REDIRECT
     */
    private function redirectToPanel($user)
    {
        if (!$user) {
            return redirect('/login');
        }

        // Admin Panel Roles
        if (in_array($user->role, ['super_admin', 'admin', 'manager'])) {
            return redirect('/admin');
        }

        // Seller Panel
        // if ($user->role === 'seller') {
        //     return redirect('/seller');
        // }

        // Default (Customer / Others)
        return redirect('/admin');
    }
}