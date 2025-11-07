<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigins = config('cors.allowed_origins', []);
        $origin = $request->headers->get('Origin');

        $isOriginAllowed = false;
        if (!$origin) {
            $isOriginAllowed = true;
        } elseif (empty($allowedOrigins) || in_array('*', $allowedOrigins, true)) {
            $isOriginAllowed = true;
        } else {
            $normalizedOrigin = rtrim($origin, '/');
            foreach ($allowedOrigins as $allowedOrigin) {
                if ($normalizedOrigin === rtrim($allowedOrigin, '/')) {
                    $isOriginAllowed = true;
                    break;
                }
            }
        }

        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 204);
        } else {
            $response = $next($request);
        }

        if ($isOriginAllowed) {
            $response->headers->set('Access-Control-Allow-Origin', $origin ?: '*');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        return $response;
    }
}

