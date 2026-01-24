<?php

namespace Dinithoshan\Racelab\Analyzers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;

/**
 * Analyzes whether operations are internal to Racelab or Laravel framework
 * and should be excluded from logging.
 */
class InternalAnalyzer
{
    /**
     * Racelab package routes that should be excluded from logging
     */
    protected static array $racelabRoutes = [
        '/racelab' => ['GET'],
        '/racelab-assets/' => ['GET'], // Pattern match - starts with
        '/api/racelabtimelineevents' => ['GET'],
        '/api/racelabtimelineevents/flush' => ['POST'],
    ];

    /**
     * Check if a request is for a Racelab package route
     */
    public static function isRacelabRoute(Request $request): bool
    {
        $uri = $request->getRequestUri();
        $path = parse_url($uri, PHP_URL_PATH) ?: $uri;
        $method = $request->method();

        // Exact route matches
        if (isset(self::$racelabRoutes[$path])) {
            $allowedMethods = self::$racelabRoutes[$path];
            return in_array($method, $allowedMethods, true);
        }

        // Pattern matches (routes that start with a prefix)
        foreach (self::$racelabRoutes as $route => $allowedMethods) {
            if (str_ends_with($route, '/') && str_starts_with($path, $route)) {
                return in_array($method, $allowedMethods, true);
            }
        }

        return false;
    }

    /**
     * Check if a database query is internal to Racelab
     */
    public static function isRacelabQuery(QueryExecuted $query): bool
    {
        // Check if query is on the Racelab database connection
        $connection = config('racelab.database.connection');

        if ($connection && $query->connectionName === $connection) {
            return true;
        }

        // Check if query is on the Racelab table
        $table = config('racelab.database.table', 'racelab_timeline_events');

        if ($table && str_contains($query->sql, $table)) {
            return true;
        }

        return false;
    }

    /**
     * Check if a query is from Laravel internal operations
     */
    public static function isLaravelInternalQuery(QueryExecuted $query, array $stackFrames = []): bool
    {
        return QueryOriginAnalyzer::isLaravelInternal($query, $stackFrames);
    }

    /**
     * Check if a query should be excluded from logging
     * (either Racelab internal or Laravel internal)
     */
    public static function shouldExcludeQuery(QueryExecuted $query, array $stackFrames = []): bool
    {
        // Exclude Racelab queries (fast check, no stack frames needed)
        if (self::isRacelabQuery($query)) {
            return true;
        }

        // Optionally exclude Laravel internal queries
        // This can be controlled via config if needed in the future
        // For now, we'll include Laravel internals but they're marked as such
        // Uncomment below if you want to exclude Laravel internals:
        // if (self::isLaravelInternalQuery($query, $stackFrames)) {
        //     return true;
        // }

        return false;
    }

    /**
     * Check if a request should be excluded from logging
     */
    public static function shouldExcludeRequest(Request $request): bool
    {
        return self::isRacelabRoute($request);
    }
}
