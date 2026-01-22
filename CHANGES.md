# RaceLab Architecture Changes

## Summary

Complete refactoring of RaceLab's event collection and timeline architecture to solve the core problem: **maintaining context across multiple queries within a single request**.

## The Problem

**Original Issue**:
- When multiple queries executed in a request, the service provider fired for each query
- The tick profiler buffer was drained on each query (`drain()` method)
- Subsequent queries lost execution context from earlier in the request
- No way to correlate events from the same request
- Difficult to understand which function triggered which query

## The Solution

**New Architecture**:
- Event collector gathers ALL events throughout request lifecycle
- Tick profiler buffer is preserved (snapshot instead of drain)
- Single flush at request end maintains full context
- Request ID correlates all events from same execution
- Hierarchical timeline UI shows complete execution flow

## Files Added

### Core Architecture
1. **`src/Context/RequestContext.php`** ✨ NEW
   - Manages request lifecycle context
   - Generates unique request IDs
   - Tracks elapsed time and metadata

2. **`src/Collectors/EventCollector.php`** ✨ NEW
   - Centralized event collection service
   - Records all event types (queries, HTTP, cache, jobs, etc.)
   - Maintains event sequence within request
   - Registers single flush hook at request end

3. **`src/Http/Middleware/RacelabMiddleware.php`** ✨ NEW
   - Captures HTTP request/response boundaries
   - Initializes request context
   - Records HTTP events

### Documentation
4. **`ARCHITECTURE.md`** ✨ NEW
   - Comprehensive architecture documentation
   - Component descriptions and data flow
   - Debugging workflow and examples

5. **`IMPLEMENTATION_GUIDE.md`** ✨ NEW
   - Step-by-step installation guide
   - Testing and verification instructions
   - Configuration and troubleshooting

6. **`CHANGES.md`** ✨ NEW (this file)
   - Summary of all changes made

### Frontend
7. **`resources/js/TimelineViewer.jsx`** ✨ NEW
   - Hierarchical timeline component
   - Expandable request cards
   - Color-coded event types with icons
   - Event detail views

## Files Modified

### Backend

1. **`src/Profilers/TickProfiler.php`** 🔧 MODIFIED
   - Added `getSnapshot()` method - returns frames without clearing buffer
   - Added `getBuffer()` method - access buffer without side effects
   - Added `clear()` method - explicit buffer clearing
   - Kept `drain()` method for backward compatibility (now only used at request end)

2. **`src/Watchers/StackTraceWatcher.php`** 🔧 MODIFIED
   - Changed from `drain()` to `getSnapshot(50)` to preserve buffer
   - Now uses `EventCollector` instead of `StackTraceRecorder` directly
   - Simplified event recording logic
   - Removed timeline management (delegated to EventCollector)

3. **`src/Recorders/StackTraceRecorder.php`** 🔧 MODIFIED
   - Complete refactoring
   - Removed `$timeline` array (now in EventCollector)
   - Simplified to just initialize and flush
   - Registers callback with EventCollector
   - Handles persistence and error logging

4. **`src/Recorders/TimelineEventStore.php`** 🔧 MODIFIED
   - Updated `buildRows()` to include new fields:
     - `request_id` - UUID for request correlation
     - `sequence` - Event order within request
     - `elapsed_time` - Time since request start
   - Enhanced `encodePayload()` to handle multiple event types:
     - `query`, `http_request`, `http_response`
     - `controller`, `model_event`, `exception`
     - `cache`, `job`, `command`

5. **`src/RacelabServiceProvider.php`** 🔧 MODIFIED
   - Added imports for new classes
   - Added `registerMiddleware()` method
   - Calls `RequestContext::initialize()` early
   - Calls `StackTraceRecorder::initialize()` to setup flush hooks
   - Registers middleware for HTTP boundary capture

6. **`config/racelab.php`** 🔧 MODIFIED
   - Added `capture_http_boundaries` option
   - Added `capture_headers` option  
   - Added documentation comments
   - Explicit `tick_capacity` configuration

7. **`src/Http/Controllers/TimeLineController.php`** 🔧 MODIFIED
   - Enhanced `index()` method to group events by request_id
   - Calculates request-level statistics (query count, duration, etc.)
   - Decodes JSON payloads for frontend
   - Limits results to prevent overwhelming UI

8. **`database/migrations/2025_12_27_000000_create_racelab_timeline_events_table.php`** 🔧 MODIFIED
   - Added `request_id` column (UUID)
   - Added `sequence` column (integer)
   - Added `elapsed_time` column (double)
   - Added composite index on `(request_id, sequence)`
   - Added index on `request_id`
   - Added column comments for documentation

### Frontend

9. **`resources/js/TimelineEvents.jsx`** 🔧 MODIFIED
   - Complete UI overhaul
   - Now uses `TimelineViewer` component
   - Added auto-refresh functionality
   - Enhanced header with statistics
   - Modern, gradient design
   - Better loading states

## Key Architectural Changes

### 1. Event Collection Flow

**Before**:
```
Query → StackTraceWatcher → drain() buffer → StackTraceRecorder → store
Query → StackTraceWatcher → drain() buffer → StackTraceRecorder → store (lost context!)
```

**After**:
```
Query 1 → EventCollector (buffer preserved)
Query 2 → EventCollector (buffer preserved)
Query N → EventCollector (buffer preserved)
Request End → Flush ALL events → TimelineEventStore
```

### 2. Context Preservation

**Before**:
```php
// First query
$frames = TickProfiler::drain(); // Buffer cleared!
recordQuery($query1, $frames);

// Second query
$frames = TickProfiler::drain(); // Empty buffer, no context!
recordQuery($query2, $frames);
```

**After**:
```php
// First query
$frames = TickProfiler::getSnapshot(50); // Buffer preserved
EventCollector::recordQuery(...);
EventCollector::recordStackFrames($frames);

// Second query
$frames = TickProfiler::getSnapshot(50); // Still has full buffer!
EventCollector::recordQuery(...);
EventCollector::recordStackFrames($frames);

// Request end
StackTraceRecorder::flushTimeline(EventCollector::getEvents());
TickProfiler::clear(); // Now we clear
```

### 3. Request Correlation

**Before**:
```
No way to group events from same request
```

**After**:
```php
RequestContext::initialize(); // Generates UUID
// All events include request_id
EventCollector::record('query', [...]) // Includes request_id automatically
```

### 4. Multiple Event Types

**Before**:
```
- query
- stack
```

**After**:
```
- query
- stack
- http_request
- http_response
- controller
- model_event
- exception
- cache
- job
- command
```

### 5. UI Improvements

**Before**:
```
Flat table of all events
Hard to understand flow
No grouping or hierarchy
```

**After**:
```
Hierarchical timeline grouped by request
Expandable request cards
Statistics per request
Color-coded event types
Click for details
```

## Database Schema Changes

### New Columns

```sql
-- Groups events from same request/job/command
request_id UUID NULL

-- Order of events within a request
sequence INTEGER NULL

-- Time elapsed since request start (milliseconds)
elapsed_time DOUBLE NULL
```

### New Indexes

```sql
-- For querying events by request
CREATE INDEX idx_request_id ON racelab_timeline_events (request_id);

-- For ordering events within request
CREATE INDEX idx_request_sequence ON racelab_timeline_events (request_id, sequence);
```

## Configuration Changes

### New Options

```php
// Capture HTTP request/response boundaries
'capture_http_boundaries' => env('RACELAB_CAPTURE_HTTP', true),

// Capture HTTP headers (can be verbose)
'capture_headers' => env('RACELAB_CAPTURE_HEADERS', false),

// Explicit tick capacity configuration
'tick_capacity' => env('RACELAB_TICK_CAPACITY', 10000),
```

## API Changes

### EventCollector (NEW)

```php
// Record any event type
EventCollector::record(string $type, array $data);

// Specialized recording methods
EventCollector::recordQuery($sql, $bindings, $timeMs, $connection);
EventCollector::recordHttpRequest($method, $uri, $headers);
EventCollector::recordHttpResponse($statusCode, $headers);
EventCollector::recordException($exception);
// ... and more

// Get collected events
EventCollector::getEvents(): array;

// Register flush hook
EventCollector::registerFlushHook(callable $callback);
```

### TickProfiler

```php
// NEW: Get snapshot without clearing
TickProfiler::getSnapshot(int $limit = 100): array;

// NEW: Get full buffer
TickProfiler::getBuffer(): array;

// NEW: Clear buffer explicitly
TickProfiler::clear(): void;

// EXISTING: Drain buffer (now only used at request end)
TickProfiler::drain(): array;
```

### RequestContext (NEW)

```php
// Initialize request context
RequestContext::initialize(string $type, array $metadata);

// Get request ID
RequestContext::getRequestId(): ?string;

// Get elapsed time since request start
RequestContext::getElapsedTime(): float;

// Add metadata
RequestContext::addMetadata(string $key, mixed $value);
```

## Breaking Changes

### Database Schema

**BREAKING**: New columns added to `racelab_timeline_events` table.

**Migration Required**: 
- Drop and recreate table, OR
- Add columns via migration (see IMPLEMENTATION_GUIDE.md)

### StackTraceRecorder API

**BREAKING**: Public API changed significantly.

**Before**:
```php
StackTraceRecorder::logQueryEvent($query, $stack);
StackTraceRecorder::recordTimeline($query, $stack);
StackTraceRecorder::registerFlushHook();
```

**After**:
```php
StackTraceRecorder::initialize(); // Called once by service provider
// Internal implementation changed to use EventCollector
```

**Impact**: If you were calling these methods directly, update to use `EventCollector` instead.

### Timeline Controller Response

**BREAKING**: API response structure changed.

**Before**:
```json
{
  "success": true,
  "data": [
    { "id": 1, "type": "query", ... },
    { "id": 2, "type": "stack", ... }
  ]
}
```

**After**:
```json
{
  "success": true,
  "total_requests": 5,
  "data": [
    {
      "request_id": "uuid",
      "started_at": 1234567890.123,
      "duration": 150.5,
      "query_count": 3,
      "events": [...]
    }
  ]
}
```

**Impact**: If you have custom UI consuming this API, update to use new structure.

## Non-Breaking Changes

All other changes are backward compatible or internal implementation details that don't affect external API.

## Migration Path

### From Old RaceLab to New RaceLab

1. **Backup your timeline database** (optional, can just clear it)
   ```bash
   cp storage/app/racelab_timeline.sqlite storage/app/racelab_timeline.backup.sqlite
   ```

2. **Update database schema**
   ```bash
   php artisan migrate
   ```
   Or drop and recreate if you don't need old data.

3. **Update configuration**
   ```bash
   php artisan vendor:publish --tag=racelab-config --force
   ```

4. **Rebuild frontend assets**
   ```bash
   cd racelab
   npm run build
   php artisan vendor:publish --tag=racelab-assets --force
   ```

5. **Clear caches**
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

6. **Test**
   - Visit your app to generate events
   - Open `/racelab` to view timeline
   - Verify queries show proper context

## Testing the Changes

### Verify Context Preservation

Create a test endpoint:

```php
Route::get('/test-racelab', function () {
    $user = User::first();           // Query 1
    $posts = $user->posts;           // Query 2
    $comments = $posts[0]->comments; // Query 3
    return 'done';
});
```

Visit `/racelab` and expand the request:
- Should see 3 queries
- Each query should have stack frames
- Stack frames should show the actual code that triggered each query
- Timeline should show chronological order

### Verify Request Correlation

Make 3 requests to different endpoints.

Visit `/racelab`:
- Should see 3 separate request cards
- Each request should have its own request_id
- Events should be grouped correctly
- No mixing of events between requests

## Performance Impact

### Before
- Tick profiler overhead: Same
- Query listener: Fired N times for N queries
- Flush: Happened N times (inefficient)

### After
- Tick profiler overhead: Same (or better with snapshot)
- Query listener: Still fires N times, but cheaper
- Flush: Happens once at request end (efficient)

**Net Result**: Similar or slightly better performance, much better functionality.

## Future Enhancements

Possible future improvements:

1. **Real-time updates** - WebSocket push of new events
2. **Filtering** - Filter events by type, search, etc.
3. **Visualization** - Flame graphs, waterfalls, etc.
4. **Comparison** - Compare timelines across requests
5. **Persistence options** - MySQL, PostgreSQL support
6. **Export** - Export timeline as JSON, PDF, etc.
7. **Smart truncation** - Auto-delete old events after N days
8. **Profiling mode** - Even deeper analysis with xdebug/xhprof integration

## Credits

Architecture redesigned to solve the query context problem described by the user.

## Version

This represents version 2.0 of the RaceLab architecture.

- v1.0: Original implementation with query tracking
- v2.0: Complete refactoring with request-scoped context preservation

## License

Same as original RaceLab package.
