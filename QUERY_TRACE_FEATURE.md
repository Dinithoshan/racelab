# Query-to-Stack-Trace Linking Feature

## Overview

This feature enhances RaceLab by linking each database query to its complete stack trace, allowing developers to see exactly which code path triggered each query. The system uses an intelligent engine to analyze stack traces and identify the most relevant frame (typically application code) for display.

## Architecture

### Design Principles

The implementation follows **SOLID principles** and PHP best practices:

- **Single Responsibility**: Each class has a single, well-defined purpose
  - `QueryTraceMapper`: Analyzes and maps traces
  - `StackTraceWatcher`: Captures traces on query execution
  - `EventCollector`: Collects events with trace data
  - `TimelineEventStore`: Persists trace data

- **Open/Closed**: Extensible without modification
  - Trace analysis can be extended with new rules
  - New trace sources can be added

- **Dependency Inversion**: Depends on abstractions
  - Uses interfaces and dependency injection where appropriate
  - Configurable via Laravel config

## Database Schema

### New Columns

The following columns were added to the `racelab_timeline_events` table:

#### `parent_event_id` (unsignedBigInteger, nullable)
- **Purpose**: Links child events (like stack frames) to their parent query events
- **Index**: Yes (`idx_parent_event_id`)
- **Usage**: Currently reserved for future hierarchical event relationships

#### `trace_summary` (text, nullable)
- **Purpose**: Stores the analyzed trace summary (JSON) - the most relevant frame identified by the engine
- **Content**: JSON object containing:
  ```json
  {
    "file": "/path/to/file.php",
    "line": 42,
    "class": "App\\Services\\UserService",
    "function": "getUser",
    "is_vendor": false,
    "source": "application",
    "description": "UserService::getUser() - UserService.php:42",
    "short_path": "app/Services/UserService.php"
  }
  ```

### Updated Payload Structure

Query events now include stack trace information in their `payload` JSON:

```json
{
  "sql": "SELECT * FROM users WHERE id = ?",
  "bindings": [1],
  "time_ms": 2.5,
  "connection": "mysql",
  "origin": {
    "type": "application",
    "label": "Application Code",
    "file": "/path/to/file.php",
    "line": 42,
    "class": "App\\Services\\UserService",
    "function": "getUser",
    "description": "UserService::getUser() - UserService.php:42"
  },
  "stack_trace": [
    {
      "file": "/path/to/file.php",
      "line": 42,
      "class": "App\\Services\\UserService",
      "function": "getUser",
      "is_vendor": false,
      "source": "application",
      "description": "UserService::getUser() - UserService.php:42",
      "short_path": "app/Services/UserService.php"
    },
    {
      "file": "/path/to/controller.php",
      "line": 15,
      "class": "App\\Http\\Controllers\\UserController",
      "function": "show",
      "is_vendor": false,
      "source": "application",
      "description": "UserController::show() - UserController.php:15",
      "short_path": "app/Http/Controllers/UserController.php"
    }
    // ... more frames
  ]
}
```

## Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    Query Execution                          │
│         (Laravel Database Query Event Fired)                │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│              StackTraceWatcher::register()                  │
│  • Listens to DB::listen() events                           │
│  • Captures debug_backtrace() on each query                 │
│  • Filters out RaceLab and framework internals              │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│            QueryTraceMapper::mapQueryToTrace()              │
│  Engine analyzes stack trace to find:                       │
│  1. Application code (preferred)                            │
│  2. Meaningful vendor code (fallback)                       │
│  3. Any valid frame (last resort)                           │
│                                                             │
│  Returns:                                                   │
│  • traceSummary: Most relevant frame with metadata          │
│  • fullTrace: Complete enriched trace array                 │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│          EventCollector::recordQuery()                      │
│  Stores query event with:                                   │
│  • SQL, bindings, timing                                    │
│  • Full stack trace array                                   │
│  • Trace summary (analyzed frame)                           │
│  • Origin metadata (backward compatibility)                 │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│        StackTraceRecorder::flushTimeline()                  │
│  At request end, persists all collected events              │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│         TimelineEventStore::persist()                       │
│  • Encodes full trace in payload JSON                       │
│  • Encodes trace summary in trace_summary column            │
│  • Inserts into database                                    │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│         TimeLineController::index()                         │
│  API endpoint returns:                                      │
│  • Events with decoded_payload (includes stack_trace)       │
│  • Events with decoded_trace_summary                        │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│              TimelineViewer.jsx (UI)                        │
│  Displays:                                                   │
│  • Trace summary prominently (from engine)                  │
│  • Expandable full stack trace                              │
│  • Color-coded by source (application/vendor/framework)     │
└─────────────────────────────────────────────────────────────┘
```

## Components

### 1. QueryTraceMapper Engine

**Location**: `src/Engines/QueryTraceMapper.php`

**Responsibilities**:
- Analyzes raw stack traces from `debug_backtrace()`
- Identifies the most relevant frame (prefers application code)
- Enriches frames with metadata (short paths, descriptions, source types)
- Provides full trace array with all frames

**Key Methods**:
- `mapQueryToTrace(array $stackFrames): ?array` - Returns the most relevant frame
- `getFullTrace(array $stackFrames): array` - Returns all enriched frames
- `findApplicationFrame()` - Finds first non-vendor, non-framework frame
- `findMeaningfulVendorFrame()` - Finds third-party package frames
- `enrichFrame()` - Adds metadata to frames

**Trace Source Types**:
- `application`: User's application code
- `vendor`: Third-party packages
- `framework`: Laravel/Illuminate code
- `racelab`: RaceLab internal code
- `unknown`: Unable to classify

### 2. StackTraceWatcher

**Location**: `src/Watchers/StackTraceWatcher.php`

**Changes**:
- Now captures `debug_backtrace()` on every query (expensive but acceptable for dev)
- Uses `QueryTraceMapper` to analyze traces
- Passes full trace and summary to `EventCollector`

**Performance Note**: 
`debug_backtrace()` is expensive but acceptable since this package is designed for local development only.

### 3. EventCollector

**Location**: `src/Collectors/EventCollector.php`

**Updated Method**:
```php
public static function recordQuery(
    string $sql, 
    array $bindings, 
    float $timeMs, 
    string $connection,
    array $origin = [],
    array $fullTrace = [],
    ?array $traceSummary = null
): void
```

### 4. TimelineEventStore

**Location**: `src/Recorders/TimelineEventStore.php`

**Changes**:
- `encodePayload()`: Includes `stack_trace` in query payload
- `encodeTraceSummary()`: New method to encode trace summary to JSON
- `buildRows()`: Sets `trace_summary` column from analyzed summary

### 5. TimeLineController

**Location**: `src/Http/Controllers/TimeLineController.php`

**Changes**:
- Decodes `trace_summary` column as `decoded_trace_summary`
- Returns both `decoded_payload` (with `stack_trace`) and `decoded_trace_summary`

### 6. TimelineViewer UI

**Location**: `resources/js/TimelineViewer.jsx`

**New Features**:
- `renderTraceSummary()`: Displays the analyzed trace summary prominently
- `renderFullTrace()`: Shows expandable full stack trace with color coding
- Trace frames are color-coded by source type
- Application code frames are highlighted

## Usage

### For Developers

1. **View Trace Summary**: Each query event now shows the most relevant frame from the trace analysis engine
2. **View Full Trace**: Click "Show Full Stack Trace" to see the complete execution path
3. **Identify Query Origin**: Quickly see which application code triggered each query

### Example Output

**Trace Summary Display**:
```
📍 Query Origin: app/Services/UserService.php:42
UserService::getUser() - UserService.php:42
Source: Application Code
```

**Full Trace Display** (expandable):
```
#0 App\Services\UserService::getUser() - app/Services/UserService.php:42 [application]
#1 App\Http\Controllers\UserController::show() - app/Http/Controllers/UserController.php:15 [application]
#2 Illuminate\Routing\Controller::callAction() - vendor/laravel/framework/... [framework]
...
```

## Migration

To apply the schema changes:

```bash
php artisan migrate
```

The migration adds:
- `parent_event_id` column (for future use)
- `trace_summary` column (stores analyzed trace)
- Index on `parent_event_id`

## Performance Considerations

1. **debug_backtrace() Cost**: Called on every query execution
   - Acceptable for local development
   - Should be disabled in production (via `racelab.enabled` config)

2. **Storage**: Full stack traces stored in JSON payload
   - May increase database size
   - Consider periodic cleanup for long-running dev environments

3. **Memory**: Stack traces stored in memory until request end
   - Monitor memory usage in requests with many queries

## Backward Compatibility

- The `origin` field in query payloads is still populated for backward compatibility
- Existing code using `origin` will continue to work
- New code should prefer `trace_summary` and `stack_trace` from payload

## Future Enhancements

1. **Hierarchical Events**: Use `parent_event_id` to link related events
2. **Trace Filtering**: Configurable rules for what to include/exclude
3. **Trace Compression**: Store only relevant frames to reduce storage
4. **Trace Comparison**: Compare traces across requests to find patterns
5. **Performance Profiling**: Aggregate trace data to identify slow code paths

## Testing

To test the feature:

1. Run a Laravel application with RaceLab enabled
2. Execute queries (e.g., via a controller)
3. View the timeline at `/racelab`
4. Expand a query event to see:
   - Trace summary (analyzed frame)
   - Full stack trace (expandable)

## Configuration

No new configuration options are required. The feature works automatically when:
- `racelab.enabled` is `true`
- Database connection is configured
- Migrations have been run
