<?php

namespace Dinithoshan\Racelab\Recorders;

use Dinithoshan\Racelab\Collectors\EventCollector;
use Dinithoshan\Racelab\Profilers\TickProfiler;
use Throwable;

/**
 * Records timeline events to persistent storage.
 * Works in conjunction with EventCollector to flush events at request end.
 */
class StackTraceRecorder
{
    protected static bool $initialized = false;

    /**
     * Initialize the recorder and register flush hook
     */
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;

        // Register the flush callback with EventCollector
        EventCollector::registerFlushHook(function (array $events): void {
            self::flushTimeline($events);
        });
    }

    /**
     * Flush timeline events to persistent storage
     */
    protected static function flushTimeline(array $events): void
    {
        if (empty($events)) {
            return;
        }

        try {
            TimelineEventStore::persist($events);

            // Clear tick profiler buffer after successful flush
            if (TickProfiler::enabled()) {
                TickProfiler::clear();
            }

            // Log if configured
            if (config('racelab.logging_enabled')) {
                logger()->info('Racelab timeline flushed', [
                    'event_count' => count($events),
                    'connection' => TimelineEventStore::connectionName(),
                    'table' => TimelineEventStore::tableName(),
                ]);
            }
        } catch (Throwable $exception) {
            logger()->warning('Failed to store Racelab timeline', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'connection' => TimelineEventStore::connectionName(),
                'table' => TimelineEventStore::tableName(),
            ]);
        }
    }

    /**
     * Check if recorder is initialized
     */
    public static function isInitialized(): bool
    {
        return self::$initialized;
    }
}