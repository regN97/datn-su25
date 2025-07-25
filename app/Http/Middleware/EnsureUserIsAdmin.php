<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->role_id === 1) {
            return $next($request);
        }

        abort(403, 'Bạn không có quyền truy cập (ADMIN).');
    }
}