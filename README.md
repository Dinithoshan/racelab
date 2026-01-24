# 🏁 RaceLab

> A powerful debugging package for Laravel that tracks database queries, stack traces, and execution flow to help you understand exactly which code path triggered each query.

## The Problem

When debugging Laravel applications, you often see database queries in logs, but you don't know:
- **Which function called this query?**
- **How did execution flow reach this point?**
- **Which queries belong to the same request?**
- **What's the complete execution timeline?**

Traditional query logging shows you WHAT queries ran, but not WHY they ran.

## The Solution

RaceLab captures the **complete execution timeline** of your Laravel application:

Every database query with its full stack trace  
HTTP request and response boundaries  
Controller actions and model events  
Cache operations and queue jobs  
Complete execution flow from start to finish  
Beautiful timeline UI showing everything in context  

Now you can see **exactly** which code path led to each query!

## Features

### 🔍 Complete Query Tracing
- Capture all database queries with SQL, bindings, and execution time
- Full stack trace showing exactly which code triggered each query
- Works with multiple queries in the same request (context preserved!)

### 📊 Timeline Visualization
- Beautiful, hierarchical timeline UI
- Group events by request with expandable cards
- Color-coded event types with icons
- Click any event to see full details

### Request Correlation
- Unique request ID groups all events together
- See event count, query count, and total query time per request
- Chronological ordering with sequence numbers
- Elapsed time since request start for each event

### Multiple Event Types
- **Database queries** - SQL, bindings, execution time
- **HTTP requests** - Method, URI, headers (optional)
- **HTTP responses** - Status code, headers (optional)
- **Stack traces** - Complete execution path
- **Controller actions** - Which controller handled the request
- **Model events** - Creating, updating, deleting, etc.
- **Cache operations** - Get, put, forget, etc.
- **Queue jobs** - Job execution tracking
- **Artisan commands** - CLI command tracking
- **Exceptions** - Error tracking with stack traces

### ⚡ Smart Performance
- Uses PHP's tick mechanism for complete coverage
- Configurable buffer capacity
- Single flush at request end (not per query!)
- Separate SQLite database (no app DB impact)
- Development-only (never runs in production)

## Installation

### 1. Require the Package

```bash
composer require dinithoshan/racelab --dev
```

```bash
php artisan vendor:publish --provider="Dinithoshan\Racelab\RacelabServiceProvider"
```

This creates the `racelab_timeline_events` table in a separate SQLite database.

### 4. Configure (Optional)

Edit `config/racelab.php` to customize settings:

```php
return [
    'enabled' => env('RACELAB_ENABLED', true),
    'tick_capacity' => env('RACELAB_TICK_CAPACITY', 10000),
    'capture_http_boundaries' => true,
    'capture_headers' => false,
    // ... more options
];
```

### 5. Access the Dashboard

Visit `http://your-app.test/racelab` to see the timeline!

## Usage

### Basic Usage

RaceLab works automatically once installed. Just use your Laravel app normally:

```php
Route::get('/users/{id}', function ($id) {
    $user = User::find($id);        // Query 1
    $posts = $user->posts;          // Query 2  
    $comments = $posts->flatMap->comments; // Query 3
    
    return view('user.profile', compact('user'));
});
```

Visit `/racelab` to see:
- The HTTP request to `/users/123`
- The controller/route handling it
- Stack frames leading to `User::find()`
- Query: `SELECT * FROM users WHERE id = 123`
- Stack frames leading to `$user->posts`
- Query: `SELECT * FROM posts WHERE user_id = 123`
- Stack frames leading to `comments` access
- Query: `SELECT * FROM comments WHERE post_id IN (...)`
- The HTTP response with status code

**You can see exactly which line of code triggered each query!**

### Viewing the Timeline

Navigate to `/racelab` in your browser.

**Request Cards** show:
- Request ID (UUID)
- Start time
- Total events captured
- Number of queries
- Total query execution time
- Total request duration

Click a request to **expand the timeline** and see:
- All events in chronological order
- Color-coded event types
- Elapsed time for each event

Click any event to see **full details**:
- File path and line number
- Class and method name
- SQL with bindings (for queries)
- Full payloads

### Advanced Features

#### Auto-Refresh

Enable auto-refresh to watch events in real-time:
- Click the "Auto-refresh" checkbox
- Events update every 2 seconds
- Great for development and debugging

#### Clear Events

Click "Clear Events" to flush old timeline data.

Or via command line:

```bash
php artisan racelab:flush
```

#### Custom Events

Record custom events in your code:

```php
use Dinithoshan\Racelab\Collectors\EventCollector;

EventCollector::record('custom_event', [
    'action' => 'user_login',
    'user_id' => $user->id,
    'ip' => request()->ip(),
]);
```

See `IMPLEMENTATION_GUIDE.md` for more details on custom events.

## Configuration

### Environment Variables

```env
# Enable/disable RaceLab
RACELAB_ENABLED=true

# Tick profiler buffer capacity (higher = more memory, more context)
RACELAB_TICK_CAPACITY=10000

# Capture HTTP request/response details
RACELAB_CAPTURE_HTTP=true

# Capture HTTP headers (verbose)
RACELAB_CAPTURE_HEADERS=false

# Enable internal logging
RACELAB_LOGGING_ENABLED=false

# Database configuration
RACELAB_DB_CONNECTION=racelab_timeline
RACELAB_DB_PATH=/path/to/storage/app/racelab_timeline.sqlite
RACELAB_DB_TABLE=racelab_timeline_events
```

### Configuration File

Full configuration in `config/racelab.php`:

```php
return [
    'enabled' => env('RACELAB_ENABLED', true),
    
    'database' => [
        'connection' => env('RACELAB_DB_CONNECTION', 'racelab_timeline'),
        'path' => env('RACELAB_DB_PATH', storage_path('app/racelab_timeline.sqlite')),
        'table' => env('RACELAB_DB_TABLE', 'racelab_timeline_events'),
    ],
    
    'tick_capacity' => env('RACELAB_TICK_CAPACITY', 10000),
    'capture_http_boundaries' => env('RACELAB_CAPTURE_HTTP', true),
    'capture_headers' => env('RACELAB_CAPTURE_HEADERS', false),
    'logging_enabled' => env('RACELAB_LOGGING_ENABLED', false),
];
```

## Architecture

### How It Works

1. **Tick Profiler** captures execution at every statement
2. **Event Collector** gathers all events throughout request lifecycle
3. **Watchers** listen for queries, exceptions, etc. and record events
4. **Request Context** maintains correlation between events
5. **Flush Hook** persists everything to database at request end
6. **Timeline Store** saves events to separate SQLite database
7. **React UI** displays hierarchical timeline with full context

### Key Components

- **`RequestContext`** - Tracks request lifecycle and generates unique IDs
- **`EventCollector`** - Centralized event collection service
- **`TickProfiler`** - Captures execution flow using PHP ticks
- **`StackTraceWatcher`** - Listens for database queries
- **`StackTraceRecorder`** - Manages event persistence
- **`TimelineEventStore`** - Persists events to database
- **`RacelabMiddleware`** - Captures HTTP boundaries

See `ARCHITECTURE.md` for detailed technical documentation.

## Performance

### Development Impact

With tick profiler enabled:
- **2-5x slower** request execution
- **~3-5MB** additional memory per request
- **Acceptable for development**
- **NEVER use in production!**

### Optimization

If too slow, reduce tick capacity:

```php
'tick_capacity' => 5000, // or even 1000
```

Or disable tick profiler entirely:

```php
'tick_capacity' => 0, // Falls back to debug_backtrace()
```

### Production Safety

RaceLab is designed for **development only**:

```php
// Automatically disabled in production
'enabled' => env('RACELAB_ENABLED', env('APP_ENV') !== 'production'),
```

**CRITICAL**: Always ensure RaceLab is disabled in production!

## Screenshots

### Timeline Dashboard
Beautiful hierarchical timeline showing all requests with statistics.

### Request Details
Expand a request to see all events in chronological order.

### Event Details
Click an event to see full details including stack traces and payloads.

## Troubleshooting

### No Events Showing

1. Check configuration: `php artisan tinker` → `config('racelab.enabled')`
2. Check database: `storage/app/racelab_timeline.sqlite` exists
3. Check migrations: `php artisan migrate:status`
4. Check logs: `tail -f storage/logs/laravel.log`

### Performance Too Slow

1. Reduce tick capacity: `RACELAB_TICK_CAPACITY=5000`
2. Disable tick profiler: `RACELAB_TICK_CAPACITY=0`
3. Disable HTTP capture: `RACELAB_CAPTURE_HTTP=false`

### Frontend Not Loading

1. Assets are served automatically from the package - no publishing needed
2. Check that routes are loaded: `php artisan route:list | grep racelab`
3. Check browser console for errors
4. Verify package dist folder exists: `vendor/dinithoshan/racelab/dist/`

See `IMPLEMENTATION_GUIDE.md` for more troubleshooting tips.

## Documentation

- **`README.md`** (this file) - Overview and quick start
- **`ARCHITECTURE.md`** - Detailed technical architecture
- **`IMPLEMENTATION_GUIDE.md`** - Step-by-step setup and usage
- **`CHANGES.md`** - Complete list of changes and migration guide

## Requirements

- **PHP**: 8.1 or higher
- **Laravel**: 10.x or 11.x
- **SQLite**: For timeline database
- **Node.js**: For building frontend assets (if modifying)

## Contributing

This is a personal development package, but suggestions and issues are welcome!

## License

MIT License - use freely in your Laravel projects.

## Security

**NEVER use RaceLab in production!**

RaceLab is a development tool only. It:
- Has significant performance overhead
- Stores sensitive data (queries, stack traces)
- Exposes internal application structure
- Could be a security risk if exposed

Always ensure:
```env
# Production
RACELAB_ENABLED=false
```

## Credits

Created to solve the problem of tracking database query origins in Laravel applications.

## Support

For issues, questions, or feature requests, see the documentation files or create an issue.

---

**Happy Debugging!** 🏁
