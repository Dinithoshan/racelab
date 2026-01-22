<?php

namespace Dinithoshan\Racelab\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Dinithoshan\Racelab\Context\RequestContext;
use Dinithoshan\Racelab\Collectors\EventCollector;

/**
 * Middleware that captures HTTP request/response boundaries and initializes request context.
 */
class RacelabMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('racelab.enabled')) {
            return $next($request);
        }

        // Initialize request context
        RequestContext::initialize('http', [
            'method' => $request->method(),
            'uri' => $request->getRequestUri(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Record HTTP request start
        EventCollector::recordHttpRequest(
            $request->method(),
            $request->getRequestUri(),
            config('racelab.capture_headers', false) ? $request->headers->all() : []
        );

        // Proceed with request
        $response = $next($request);

        // Record HTTP response
        EventCollector::recordHttpResponse(
            $response->getStatusCode(),
            config('racelab.capture_headers', false) ? $response->headers->all() : []
        );

        return $response;
    }
}
