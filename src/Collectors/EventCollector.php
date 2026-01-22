<?php

namespace Dinithoshan\Racelab\Collectors;

use Dinithoshan\Racelab\Context\RequestContext;

/**
 * Centralized event collector that gathers all timeline events during request execution.
 * Events are stored in memory and flushed at the end of the request lifecycle.
 */
class EventCollector
{
    protected static array $events = [];
    protected static bool $flushRegistered = false;
    protected static int $eventSequence = 0;

    /**
     * Record a timeline event
     */
    public static function record(string $type, array $data = []): void
    {
        $timestamp = microtime(true);
        $requestId = RequestContext::getRequestId();

        self::$events[] = array_merge([
            'type' => $type,
            'request_id' => $requestId,
            'sequence' => ++self::$eventSequence,
            'timestamp' => $timestamp,
            'elapsed_time' => RequestContext::getElapsedTime(),
            'process_id' => getmypid(),
        ], $data);
    }

    /**
     * Record a stack frame event
     */
    public static function recordStackFrame(array $frame): void
    {
        self::record('stack', [
            'file' => $frame['file'] ?? null,
            'line' => $frame['line'] ?? null,
            'class' => $frame['class'] ?? null,
            'function' => $frame['function'] ?? null,
            'is_vendor' => $frame['is_vendor'] ?? false,
        ]);
    }

    /**
     * Record a database query event
     */
    public static function recordQuery(
        string $sql, 
        array $bindings, 
        float $timeMs, 
        string $connection,
        array $origin = []
    ): void
    {
        self::record('query', [
            'sql' => $sql,
            'bindings' => $bindings,
            'time_ms' => $timeMs,
            'connection' => $connection,
            'origin' => $origin,
        ]);
    }

    /**
     * Record an HTTP request event
     */
    public static function recordHttpRequest(string $method, string $uri, array $headers = []): void
    {
        self::record('http_request', [
            'method' => $method,
            'uri' => $uri,
            'headers' => $headers,
        ]);
    }

    /**
     * Record an HTTP response event
     */
    public static function recordHttpResponse(int $statusCode, array $headers = []): void
    {
        self::record('http_response', [
            'status_code' => $statusCode,
            'headers' => $headers,
        ]);
    }

    /**
     * Record a controller action being called
     */
    public static function recordControllerAction(string $controller, string $action): void
    {
        self::record('controller', [
            'controller' => $controller,
            'action' => $action,
        ]);
    }

    /**
     * Record a model event (creating, updating, etc.)
     */
    public static function recordModelEvent(string $model, string $event): void
    {
        self::record('model_event', [
            'model' => $model,
            'event' => $event,
        ]);
    }

    /**
     * Record an exception
     */
    public static function recordException(\Throwable $exception): void
    {
        self::record('exception', [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Record a cache operation
     */
    public static function recordCacheOperation(string $operation, string $key, mixed $value = null): void
    {
        self::record('cache', [
            'operation' => $operation,
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Record a queue job
     */
    public static function recordJobExecution(string $jobClass, array $data = []): void
    {
        self::record('job', [
            'job_class' => $jobClass,
            'data' => $data,
        ]);
    }

    /**
     * Record an artisan command
     */
    public static function recordCommand(string $command, array $arguments = []): void
    {
        self::record('command', [
            'command' => $command,
            'arguments' => $arguments,
        ]);
    }

    /**
     * Get all collected events
     */
    public static function getEvents(): array
    {
        return self::$events;
    }

    /**
     * Get event count
     */
    public static function count(): int
    {
        return count(self::$events);
    }

    /**
     * Clear all events
     */
    public static function clear(): void
    {
        self::$events = [];
        self::$eventSequence = 0;
    }

    /**
     * Register flush hook to persist events at request end
     */
    public static function registerFlushHook(callable $flushCallback): void
    {
        if (self::$flushRegistered) {
            return;
        }

        self::$flushRegistered = true;

        // Laravel application terminating event
        if (function_exists('app')) {
            app()->terminating(function () use ($flushCallback): void {
                $flushCallback(self::$events);
                self::clear();
            });
        }

        // Fallback shutdown function
        register_shutdown_function(function () use ($flushCallback): void {
            if (! empty(self::$events)) {
                $flushCallback(self::$events);
                self::clear();
            }
        });
    }

    /**
     * Check if flush hook is registered
     */
    public static function isFlushRegistered(): bool
    {
        return self::$flushRegistered;
    }
}
