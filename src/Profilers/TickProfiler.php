<?php

namespace Dinithoshan\Racelab\Profilers;

use Dinithoshan\Racelab\DTOs\StackFrame;

final class TickProfiler
{
    protected static array $buffer = [];
    protected static int $capacity = 10000;
    protected static bool $registered = false;

    public static function register(int $capacity = 10000): void
    {   
        if (self::$registered || ! function_exists('register_tick_function')) {
            return;
        }

        self::$capacity = $capacity;
        register_tick_function([self::class, 'onTick']);
        self::$registered = true;
    }


    /**
     * Saves the callstacks in memory
     */
    public static function onTick(): void
    {
        $now = hrtime(true);
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        $caller = $trace[1] ?? $trace[0] ?? [];

        // normalize using StackFrame DTO
        $frame = StackFrame::fromBacktraceFrame((array) $caller)->toArray();
        $frame['timestamp'] = $now;

        self::$buffer[] = $frame;

        if (count(self::$buffer) > self::$capacity) {
            array_shift(self::$buffer);
        }
    }








    /**
     * Get a snapshot of the buffer without clearing it.
     * Returns frames leading up to the current point in time.
     */
    public static function getSnapshot(int $limit = 100): array
    {
        // Return the most recent N frames
        return array_slice(self::$buffer, -$limit);
    }

    /**
     * Clear the buffer of callstack frames and return the ones in memory.
     * This should only be called at the end of request lifecycle.
     */
    public static function drain(): array
    {
        $stack = self::$buffer;
        self::$buffer = [];

        return $stack;
    }

    /**
     * Get the entire buffer without clearing
     */
    public static function getBuffer(): array
    {
        return self::$buffer;
    }

    /**
     * Clear the buffer without returning anything
     */
    public static function clear(): void
    {
        self::$buffer = [];
    }


    public static function enabled(): bool
    {
        return self::$registered;
    }
}