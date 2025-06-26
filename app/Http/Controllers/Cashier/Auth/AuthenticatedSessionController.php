<?php

namespace App\Http\Controllers\Cashier\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return Inertia::render('cashier/Login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            if (Auth::user()->role_id !== 3) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Bạn không phải là thu ngân.',
                ]);
            }

            $request->session()->regenerate();
            return redirect()->intended(route('cashier.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Email hoặc mật khẩu không đúng.',
        ]);
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('cashier.login');
    }
}