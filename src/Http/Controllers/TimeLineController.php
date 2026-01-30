<?php

namespace Dinithoshan\Racelab\Http\Controllers;

use Dinithoshan\Racelab\Config\TimelineConfig;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TimeLineController extends Controller
{
    public function index()
    {
        $entries = DB::connection(TimelineConfig::connection())
            ->table(TimelineConfig::table())
            ->orderBy('occurred_at', 'desc')
            ->limit(1000) // Limit to prevent overwhelming the UI
            ->get();

        // Group entries by request_id for hierarchical view
        $grouped = $entries->groupBy('request_id')->map(function ($requestEvents) {
            $firstEvent = $requestEvents->first();
            $lastEvent = $requestEvents->last();
            
            // Decode payloads and trace summaries
            $events = $requestEvents->map(function ($event) {
                if ($event->payload) {
                    $event->decoded_payload = json_decode($event->payload, true);
                }
                
                // Decode trace summary if present
                if ($event->trace_summary) {
                    $event->decoded_trace_summary = json_decode($event->trace_summary, true);
                }
                
                // Add label property for display
                $event->label = $this->getEventLabel($event->type);
                
                return $event;
            });

            // Calculate request summary
            $queryCount = $events->where('type', 'query')->count();
            $totalQueryTime = $events->where('type', 'query')
                ->sum(function ($event) {
                    return $event->decoded_payload['time_ms'] ?? 0;
                });

            return [
                'request_id' => $firstEvent->request_id,
                'started_at' => $firstEvent->occurred_at,
                'ended_at' => $lastEvent->occurred_at,
                'duration' => ($lastEvent->occurred_at - $firstEvent->occurred_at) * 1000, // ms
                'event_count' => $events->count(),
                'query_count' => $queryCount,
                'total_query_time' => round($totalQueryTime, 2),
                'events' => $events->values(),
            ];
        })->values();

        // Get the default database connection driver for SQL formatting
        $defaultConnection = config('database.default');
        $dbDriver = config("database.connections.{$defaultConnection}.driver", 'mysql');
        
        // Map Laravel drivers to sql-formatter dialects
        $sqlDialect = match($dbDriver) {
            'mysql', 'mariadb' => 'mysql',
            'pgsql', 'postgres' => 'postgresql',
            'sqlite' => 'sqlite',
            'sqlsrv', 'mssql' => 'sql',
            default => 'sql', // fallback to generic SQL
        };

        return response()->json([
            'success' => true,
            'data' => $grouped,
            'total_requests' => $grouped->count(),
            'db_dialect' => $sqlDialect,
        ]);
    }

    public function destroy()
    {
        try {
            DB::connection(TimelineConfig::connection())
                ->table(TimelineConfig::table())
                ->truncate();
        } catch (\Exception $e) {
            Log::error('Failed to flush RaceLab timeline', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Entries have been flushed successfully'
        ]);
    }

    /**
     * Get a human-readable label for an event type
     *
     * @param string $type
     * @return string
     */
    private function getEventLabel(string $type): string
    {
        return match ($type) {
            'http_response' => 'HTTP Response',
            'http_request' => 'HTTP Request',
            'query' => 'Query',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}