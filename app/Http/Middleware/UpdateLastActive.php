<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UpdateLastActive
{
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            // Throttle the write so an active user doesn't hammer the
            // `users` table on every single request — once a minute is
            // plenty of resolution for a "last active" display.
            $cacheKey = "last-active-{$user->id}";

            if (! Cache::has($cacheKey)) {
                $user->forceFill(['last_active_at' => now()])->saveQuietly();
                Cache::put($cacheKey, true, now()->addMinute());
            }
        }

        return $next($request);
    }
}