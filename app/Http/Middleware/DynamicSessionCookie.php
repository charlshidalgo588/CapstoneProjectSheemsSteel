<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DynamicSessionCookie
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isSecure()) {
            config([
                'session.secure' => true,
                'session.same_site' => 'none',
            ]);
        } else {
            config([
                'session.secure' => false,
                'session.same_site' => 'lax',
            ]);
        }

        return $next($request);
    }
}