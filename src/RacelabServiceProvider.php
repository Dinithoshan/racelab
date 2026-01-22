<?php

namespace Dinithoshan\Racelab;

use Dinithoshan\Racelab\Commands\InstallCommand;
use Dinithoshan\Racelab\Commands\FlushDbCommand;
use Dinithoshan\Racelab\Watchers\StackTraceWatcher;
use Dinithoshan\Racelab\Profilers\TickProfiler;
use Dinithoshan\Racelab\Recorders\StackTraceRecorder;
use Dinithoshan\Racelab\Context\RequestContext;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;

class RacelabServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // load the views
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'racelab');

        // publish the config
        $this->publishes([
            __DIR__ . '/../config/racelab.php' => config_path('racelab.php'),
        ], 'racelab-config');

        // Publish prebuilt assets (dist) to the host public directory when requested
        $this->publishes([
            __DIR__ . '/../dist' => public_path('vendor/racelab'),
        ], 'racelab-assets');

        // bootstrap the internals
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            $this->commands([
                InstallCommand::class,
                FlushDbCommand::class,
            ]);
        }

        if (! config('racelab.enabled')) {
            return;
        }

        // Register middleware for HTTP requests
        if (config('racelab.capture_http_boundaries', true)) {
            $this->registerMiddleware();
        }

        // Initialize context for non-HTTP requests
        if (! $this->app->runningInConsole() || $this->app->runningUnitTests()) {
            RequestContext::initialize(
                $this->app->runningInConsole() ? 'cli' : 'http'
            );
        }

        // Register watchers and recorders
        StackTraceWatcher::register();
        StackTraceRecorder::initialize();
    }

    /**
     * Register the Racelab middleware
     */
    protected function registerMiddleware(): void
    {
        $kernel = $this->app->make(Kernel::class);

        if (method_exists($kernel, 'pushMiddleware')) {
            $kernel->pushMiddleware(\Dinithoshan\Racelab\Http\Middleware\RacelabMiddleware::class);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/racelab.php', 'racelab');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Register tick profiler early so tick handler is active before queries run.
        if ((config('racelab.enabled')) && method_exists(TickProfiler::class, 'register')) {
            TickProfiler::register((int) config('racelab.tick_capacity', 10000));
        }

        $this->ensureTimelineDatabaseConfigured();
    }

    protected function ensureTimelineDatabaseConfigured(): void
    {
        $config = $this->app['config'];

        $connectionName = $config->get('racelab.database.connection');

        if (! $connectionName) {
            $connectionName = 'racelab_timeline';
            $config->set('racelab.database.connection', $connectionName);
        }

        if ($config->get("database.connections.{$connectionName}")) {
            return;
        }

        $databasePath = $config->get('racelab.database.path') ?? $this->defaultSqlitePath();

        if (! $databasePath) {
            return;
        }

        $this->ensureDatabaseFileExists($databasePath);

        $config->set("database.connections.{$connectionName}", [
            'driver' => 'sqlite',
            'url' => null,
            'database' => $databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function defaultSqlitePath(): ?string
    {
        if (method_exists($this->app, 'storagePath')) {
            return $this->app->storagePath('app/racelab_timeline.sqlite');
        }

        if (method_exists($this->app, 'basePath')) {
            return rtrim($this->app->basePath('storage/app'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'racelab_timeline.sqlite';
        }

        return null;
    }

    protected function ensureDatabaseFileExists(string $filePath): void
    {
        $directory = dirname($filePath);

        if (! is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        if (! file_exists($filePath)) {
            @touch($filePath);
        }
    }
}