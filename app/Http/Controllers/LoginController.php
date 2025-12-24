<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showAdminLoginForm()
    {
        return view('auth.login', ['role' => 'admin']);
    }

    public function showManajerLoginForm()
    {
        return view('auth.login', ['role' => 'manajer']);
    }

    public function showUserLoginForm()
    {
        return view('auth.login', ['role' => 'user']);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // ✅ Redirect sesuai role
            switch ($user->role) {
                case 'admin':
                    return redirect()->route('admin.dashboard');
                case 'manajer':
                    return redirect()->route('manajer.dashboard');
                case 'user':
                    return redirect()->route('user.dashboard');
                default:
                    Auth::logout();
                    return redirect('/')->withErrors(['role' => 'Role tidak valid.']);
            }
        }

        return back()->withErrors(['email' => 'Email atau password salah.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
