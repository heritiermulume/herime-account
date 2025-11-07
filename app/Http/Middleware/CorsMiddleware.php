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
        $allowedHeaders = config('cors.allowed_headers', ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept']);
        $maxAge = (int) config('cors.max_age', 0);
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

        try {
            if ($request->getMethod() === 'OPTIONS') {
                $response = response('', 204);
            } else {
                $response = $next($request);
            }
        } catch (\Throwable $e) {
            app('log')->error('CORS middleware captured exception', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            report($e);

            $handler = app(\App\Exceptions\Handler::class);
            $response = $handler->render($request, $e);
        }

        if ($isOriginAllowed) {
            $response->headers->set('Access-Control-Allow-Origin', $origin ?: '*');
        }

        $response->headers->set('Vary', 'Origin');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');

        $requestHeaders = $request->headers->get('Access-Control-Request-Headers');
        if ($requestHeaders) {
            $response->headers->set('Access-Control-Allow-Headers', $requestHeaders);
        } else {
            if (in_array('*', $allowedHeaders, true)) {
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
            } else {
                $response->headers->set('Access-Control-Allow-Headers', implode(', ', array_unique(array_merge([
                    'Content-Type', 'Authorization', 'X-Requested-With', 'Accept'
                ], $allowedHeaders))));
            }
        }

        if ($maxAge > 0) {
            $response->headers->set('Access-Control-Max-Age', (string) $maxAge);
        }

        if ($isOriginAllowed) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}

