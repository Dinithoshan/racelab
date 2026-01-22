<?php

namespace Dinithoshan\Racelab\Config;

final class TimelineConfig
{
    public static function connection(): string
    {
        return (string) config('racelab.database.connection') ?: (string) config('database.default');
    }

    public static function table(): string
    {
        return (string) config('racelab.database.table', 'racelab_timeline_events');
    }

    public static function loggingEnabled(): bool
    {
        return (bool) config('racelab.logging_enabled');
    }

}