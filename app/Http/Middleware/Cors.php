<?php

namespace App\Http\Middleware;

use Closure;

class Cors
{
   public function handle($request, Closure $next)
{
    $response = $next($request);

    $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:8081');
    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

    if ($request->getMethod() === "OPTIONS") {
        return response('', 200)
            ->header('Access-Control-Allow-Origin', 'http://localhost:8081')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    return $response;
}

}
