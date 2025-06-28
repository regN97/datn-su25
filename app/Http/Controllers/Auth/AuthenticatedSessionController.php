<?php

namespace App\Http\Controllers\Auth;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
        // Kiểm tra dữ liệu đầu vào
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Thử đăng nhập với thông tin đã validate
        if (Auth::attempt($credentials)) {
            // Kiểm tra role: chỉ cho phép nếu role_id === 1 (thu ngân)
            if (Auth::user()->role_id !== 1) {
                Auth::logout(); // Đăng xuất ngay nếu không đúng role
                return back()->withErrors([
                    'email' => 'Bạn không phải là admin.',
                ]);
            }

            // Tạo lại phiên làm việc (session)
            $request->session()->regenerate();

            return to_route('dashboard')->with('reload', true);
        }

        // Nếu thông tin không đúng, trả lỗi về lại form
        return back()->withErrors([
            'email' => 'Email hoặc mật khẩu không đúng.',
        ]);
    }
    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')
            ->with('success', 'Đăng xuất thành công!');
    }
}
