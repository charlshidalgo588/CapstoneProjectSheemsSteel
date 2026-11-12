<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Usage in routes (no alias registration needed):
 *   ->middleware(EnsureRole::class . ':Administrator,Cashier')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'You do not have access to this resource.');
        }

        return $next($request);
    }
}