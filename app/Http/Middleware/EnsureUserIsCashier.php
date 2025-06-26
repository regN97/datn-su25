<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsCashier
{
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->role_id === 3) {
            return $next($request);
        }

        abort(403, 'Bạn không có quyền truy cập.');
    }
}
