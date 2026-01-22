<?php

namespace Dinithoshan\Racelab\Watchers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Dinithoshan\Racelab\Collectors\EventCollector;
use Dinithoshan\Racelab\DTOs\StackFrame;
use Dinithoshan\Racelab\Profilers\TickProfiler;
use Dinithoshan\Racelab\Analyzers\QueryOriginAnalyzer;

class StackTraceWatcher
{
    public static function register(): void
    {
        DB::listen(function (QueryExecuted $query) {
            if (self::isInternalQuery($query)) {
                return;
            }

            // Collect stack frames first
            $stackFrames = [];
            
            if (TickProfiler::enabled()) {
                $stackFrames = TickProfiler::getSnapshot(50); // Get last 50 frames leading to query
            } else {
                $stackFrames = self::captureStackTrace();
            }

            // Analyze query origin
            $origin = QueryOriginAnalyzer::getOriginLabel($query, $stackFrames);

            // Record the query event with origin information
            EventCollector::recordQuery(
                $query->sql,
                $query->bindings,
                $query->time,
                $query->connectionName,
                $origin  // Pass origin metadata
            );

            // Record stack frames
            foreach ($stackFrames as $frame) {
                if (! isset($frame['file']) || self::shouldSkipFrame($frame['file'])) {
                    continue;
                }

                EventCollector::recordStackFrame([
                    'file' => $frame['file'] ?? null,
                    'line' => $frame['line'] ?? null,
                    'class' => $frame['class'] ?? null,
                    'function' => $frame['function'] ?? null,
                    'is_vendor' => $frame['is_vendor'] ?? false,
                    'timestamp' => $frame['timestamp'] ?? null,
                ]);
            }
        });
    }

    protected static function captureStackTrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $stack = [];

        foreach ($trace as $frame) {
            if (! isset($frame['file']) || self::shouldSkipFrame($frame['file'])) {
                continue;
            }

            $stack[] = StackFrame::fromBacktraceFrame($frame)->toArray();
        }

        return $stack;
    }

    protected static function shouldSkipFrame(string $path): bool
    {
        if (str_contains($path, 'racelab')) {
            return true;
        }

        return str_contains($path, 'Illuminate/Database/')
            || str_contains($path, 'Illuminate/Events/');
    }

    protected static function isVendorPath(string $path): bool
    {
        return str_contains($path, '/vendor/') || str_contains($path, '\\vendor\\');
    }

    protected static function isInternalQuery(QueryExecuted $query): bool
    {
        $connection = config('racelab.database.connection');

        if ($connection && $query->connectionName === $connection) {
            return true;
        }

        $table = config('racelab.database.table', 'racelab_timeline_events');

        return $table ? str_contains($query->sql, $table) : false;
    }

}