<?php

namespace Dinithoshan\Racelab\Watchers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Dinithoshan\Racelab\Collectors\EventCollector;
use Dinithoshan\Racelab\DTOs\StackFrame;
use Dinithoshan\Racelab\Profilers\TickProfiler;
use Dinithoshan\Racelab\Analyzers\QueryOriginAnalyzer;
use Dinithoshan\Racelab\Analyzers\InternalAnalyzer;
use Dinithoshan\Racelab\Engines\QueryTraceMapper;

class StackTraceWatcher
{
    public static function register(): void
    {
        DB::listen(function (QueryExecuted $query) {
            // Fast check: exclude Racelab queries (doesn't need stack frames)
            if (InternalAnalyzer::isRacelabQuery($query)) {
                return;
            }

            // Capture stack trace for further analysis
            $rawStackTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $stackFrames = [];
            foreach ($rawStackTrace as $frame) {
                if (! isset($frame['file']) || self::shouldSkipFrame($frame['file'])) {
                    continue;
                }
                $stackFrames[] = StackFrame::fromBacktraceFrame($frame)->toArray();
            }

            // Additional exclusion checks (e.g., Laravel internals) can be added here
            // if (InternalAnalyzer::shouldExcludeQuery($query, $stackFrames)) {
            //     return;
            // }

            // Use TickProfiler if available (more accurate for async contexts)
            if (TickProfiler::enabled()) {
                $tickFrames = TickProfiler::getSnapshot(50);
                if (! empty($tickFrames)) {
                    $stackFrames = $tickFrames;
                }
            }

            // Analyze query origin (for backward compatibility)
            $origin = QueryOriginAnalyzer::getOriginLabel($query, $stackFrames);

            // Map query to trace using the engine
            $traceSummary = QueryTraceMapper::mapQueryToTrace($stackFrames);
            $fullTrace = QueryTraceMapper::getFullTrace($stackFrames);

            // Record the query event with stack trace information
            EventCollector::recordQuery(
                $query->sql,
                $query->bindings,
                $query->time,
                $query->connectionName,
                $origin,  // Pass origin metadata for backward compatibility
                $fullTrace,  // Full stack trace
                $traceSummary  // Analyzed trace summary
            );
        });
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

}