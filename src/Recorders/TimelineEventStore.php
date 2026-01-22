<?php

namespace Dinithoshan\Racelab\Recorders;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TimelineEventStore
{
    public static function persist(array $timeline): void
    {
        $rows = self::buildRows($timeline);

        if (empty($rows)) {
            return;
        }

        DB::connection(self::connectionName())
            ->table(self::tableName())
            ->insert($rows);
    }

    protected static function buildRows(array $timeline): array
    {
        $rows = [];
        $now = Carbon::now();

        foreach ($timeline as $entry) {
            $rows[] = [
                'type' => $entry['type'] ?? 'unknown',
                'request_id' => $entry['request_id'] ?? null,
                'sequence' => $entry['sequence'] ?? null,
                'process_id' => $entry['process_id'] ?? null,
                'occurred_at' => $entry['timestamp'] ?? microtime(true),
                'elapsed_time' => $entry['elapsed_time'] ?? null,
                'file' => $entry['file'] ?? null,
                'line' => $entry['line'] ?? null,
                'class' => $entry['class'] ?? null,
                'function' => $entry['function'] ?? null,
                'is_vendor' => (bool) ($entry['is_vendor'] ?? false),
                'payload' => self::encodePayload($entry),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }

    protected static function encodePayload(array $entry): ?string
    {
        $type = $entry['type'] ?? null;
        $payload = [];

        switch ($type) {
            case 'query':
                $payload = [
                    'sql' => $entry['sql'] ?? null,
                    'bindings' => $entry['bindings'] ?? [],
                    'time_ms' => $entry['time_ms'] ?? null,
                    'connection' => $entry['connection'] ?? null,
                    'origin' => $entry['origin'] ?? null,  // Include origin analysis
                ];
                break;

            case 'http_request':
                $payload = [
                    'method' => $entry['method'] ?? null,
                    'uri' => $entry['uri'] ?? null,
                    'headers' => $entry['headers'] ?? [],
                ];
                break;

            case 'http_response':
                $payload = [
                    'status_code' => $entry['status_code'] ?? null,
                    'headers' => $entry['headers'] ?? [],
                ];
                break;

            case 'controller':
                $payload = [
                    'controller' => $entry['controller'] ?? null,
                    'action' => $entry['action'] ?? null,
                ];
                break;

            case 'model_event':
                $payload = [
                    'model' => $entry['model'] ?? null,
                    'event' => $entry['event'] ?? null,
                ];
                break;

            case 'exception':
                $payload = [
                    'class' => $entry['class'] ?? null,
                    'message' => $entry['message'] ?? null,
                    'file' => $entry['file'] ?? null,
                    'line' => $entry['line'] ?? null,
                    'trace' => $entry['trace'] ?? null,
                ];
                break;

            case 'cache':
                $payload = [
                    'operation' => $entry['operation'] ?? null,
                    'key' => $entry['key'] ?? null,
                    'value' => $entry['value'] ?? null,
                ];
                break;

            case 'job':
                $payload = [
                    'job_class' => $entry['job_class'] ?? null,
                    'data' => $entry['data'] ?? [],
                ];
                break;

            case 'command':
                $payload = [
                    'command' => $entry['command'] ?? null,
                    'arguments' => $entry['arguments'] ?? [],
                ];
                break;

            default:
                // For stack frames and unknown types, no additional payload needed
                return null;
        }

        $encoded = json_encode($payload);

        return $encoded === false ? null : $encoded;
    }

    public static function connectionName(): string
    {
        $connection = config('racelab.database.connection');

        if ($connection) {
            return (string) $connection;
        }

        return (string) config('database.default');
    }

    public static function tableName(): string
    {
        return (string) config('racelab.database.table', 'racelab_timeline_events');
    }
}
