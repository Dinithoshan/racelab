<?php

namespace Dinithoshan\Racelab\Context;

use Illuminate\Support\Str;

/**
 * Manages the context for the current request/job/command execution.
 * Provides request ID, timing, and metadata for correlating timeline events.
 */
class RequestContext
{
    protected static ?string $requestId = null;
    protected static ?float $startTime = null;
    protected static array $metadata = [];
    protected static ?string $type = null; // 'http', 'job', 'command', 'cli'

    public static function initialize(string $type = 'http', array $metadata = []): void
    {
        self::$requestId = (string) Str::uuid();
        self::$startTime = microtime(true);
        self::$type = $type;
        self::$metadata = $metadata;
    }

    public static function getRequestId(): ?string
    {
        if (self::$requestId === null) {
            self::initialize('unknown');
        }

        return self::$requestId;
    }

    public static function getStartTime(): ?float
    {
        return self::$startTime;
    }

    public static function getElapsedTime(): float
    {
        if (self::$startTime === null) {
            return 0.0;
        }

        return microtime(true) - self::$startTime;
    }

    public static function getType(): ?string
    {
        return self::$type;
    }

    public static function getMetadata(): array
    {
        return self::$metadata;
    }

    public static function addMetadata(string $key, mixed $value): void
    {
        self::$metadata[$key] = $value;
    }

    public static function reset(): void
    {
        self::$requestId = null;
        self::$startTime = null;
        self::$metadata = [];
        self::$type = null;
    }

    public static function toArray(): array
    {
        return [
            'request_id' => self::getRequestId(),
            'start_time' => self::$startTime,
            'elapsed_time' => self::getElapsedTime(),
            'type' => self::$type,
            'metadata' => self::$metadata,
        ];
    }
}
