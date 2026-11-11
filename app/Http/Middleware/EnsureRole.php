<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Pemakaian di route: EnsureRole::class.':seller' atau EnsureRole::class.':admin,seller'
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'Aksi ini hanya untuk peran: '.implode(', ', $roles).'.');
        }

        return $next($request);
    }
}
