<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('role:admin') or 'role:admin,staff'.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless(in_array($request->user()?->role?->value, $roles, true), 403, 'Anda tidak memiliki akses ke halaman ini.');

        return $next($request);
    }
}
