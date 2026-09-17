<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    /**
     * Catches a user who was already logged in at the moment an admin
     * disabled them. Auth::attempt() at login only checks credentials,
     * so a disabled user's existing session otherwise keeps working
     * indefinitely until they happen to log out on their own. This runs
     * on every authenticated request and kills the session the instant
     * it sees status has flipped to 'disabled' — so the very next thing
     * a disabled user does (not just their next login) gets rejected.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->status === 'disabled') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'message' => 'Your account has been disabled. Contact an administrator.',
            ], 403);
        }

        return $next($request);
    }
}