<?php
// app/Http/Middleware/RedirectIfAuthenticated.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        if ($request->hasCookie('auth_token') && !empty($request->cookie('auth_token'))) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}