<?php
// app/Http/Middleware/CheckAuthToken.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAuthToken
{
    public function handle(Request $request, Closure $next)
    {
        //  dd($request->cookies->all());
        if ($request->hasCookie('auth_token') && !empty($request->cookie('auth_token'))) {
            return $next($request);
        }

        return redirect()->route('login');
    }
}