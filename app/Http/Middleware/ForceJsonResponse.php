<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        // Agar error validasi/401/404 di API selalu berupa JSON,
        // walau klien lupa mengirim header "Accept: application/json".
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
