<?php

namespace Dinithoshan\Racelab\Analyzers;

use Illuminate\Database\Events\QueryExecuted;

/**
 * Analyzes queries to determine their origin and classify them
 */
class QueryOriginAnalyzer
{
    /**
     * Laravel internal tables that are managed by the framework
     */
    protected static array $laravelInternalTables = [
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'migrations',
        'password_reset_tokens',
        'personal_access_tokens',
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
        'pulse_aggregates',
        'pulse_entries',
        'pulse_values',
    ];

    /**
     * Laravel internal namespaces that indicate framework operations
     */
    protected static array $laravelInternalNamespaces = [
        'Illuminate\\Session\\',
        'Illuminate\\Cache\\',
        'Illuminate\\Queue\\',
        'Illuminate\\Auth\\',
        'Illuminate\\Cookie\\',
        'Illuminate\\Routing\\Middleware\\',
        'Laravel\\Telescope\\',
        'Laravel\\Pulse\\',
        'Laravel\\Sanctum\\',
    ];

    /**
     * Analyze a query to determine if it's a Laravel internal operation
     */
    public static function isLaravelInternal(QueryExecuted $query, array $stackFrames = []): bool
    {
        // Check if query is on a Laravel internal table
        if (self::isInternalTable($query->sql)) {
            return true;
        }

        // Check if the originating code is from Laravel internals
        if (! empty($stackFrames)) {
            $originFrame = self::findOriginatingFrame($stackFrames);
            if ($originFrame && self::isLaravelInternalFrame($originFrame)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if SQL query is on an internal Laravel table
     */
    protected static function isInternalTable(string $sql): bool
    {
        $sql = strtolower($sql);

        foreach (self::$laravelInternalTables as $table) {
            // Match table names with common SQL patterns
            if (preg_match('/\b' . preg_quote($table, '/') . '\b/', $sql)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find the first "application code" frame (not vendor, not framework)
     */
    public static function findOriginatingFrame(array $stackFrames): ?array
    {
        foreach ($stackFrames as $frame) {
            // Skip if no file (internal PHP function)
            if (empty($frame['file'])) {
                continue;
            }

            $file = $frame['file'];
            $class = $frame['class'] ?? '';

            // Skip vendor code
            if (str_contains($file, '/vendor/') || str_contains($file, '\\vendor\\')) {
                continue;
            }

            // Skip RaceLab itself
            if (str_contains($file, 'racelab')) {
                continue;
            }

            // Skip Laravel framework paths even if not in vendor
            if (str_contains($file, 'Illuminate/')) {
                continue;
            }

            // This is likely application code!
            return $frame;
        }

        // If no app code found, try to find the last vendor frame that's not deep in Laravel
        foreach ($stackFrames as $frame) {
            if (empty($frame['file'])) {
                continue;
            }

            // If it's vendor code but not Illuminate, it might be useful
            $file = $frame['file'];
            if (str_contains($file, '/vendor/') && ! str_contains($file, '/illuminate/')) {
                return $frame;
            }
        }

        // Last resort: return the first frame with a file
        foreach ($stackFrames as $frame) {
            if (! empty($frame['file'])) {
                return $frame;
            }
        }

        return null;
    }

    /**
     * Check if a frame is from Laravel internal code
     */
    protected static function isLaravelInternalFrame(array $frame): bool
    {
        $class = $frame['class'] ?? '';

        if (empty($class)) {
            return false;
        }

        foreach (self::$laravelInternalNamespaces as $namespace) {
            if (str_starts_with($class, $namespace)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a human-readable label for the query origin
     */
    public static function getOriginLabel(QueryExecuted $query, array $stackFrames = []): array
    {
        $isInternal = self::isLaravelInternal($query, $stackFrames);
        
        if ($isInternal) {
            return [
                'type' => 'laravel_internal',
                'label' => 'Laravel Internals',
                'description' => self::getInternalDescription($query->sql),
            ];
        }

        $originFrame = self::findOriginatingFrame($stackFrames);
        
        if ($originFrame) {
            return [
                'type' => 'application',
                'label' => 'Application Code',
                'file' => $originFrame['file'],
                'line' => $originFrame['line'],
                'class' => $originFrame['class'] ?? null,
                'function' => $originFrame['function'] ?? null,
                'description' => self::formatFrameDescription($originFrame),
            ];
        }

        return [
            'type' => 'unknown',
            'label' => 'Unknown',
            'description' => 'Unable to determine query origin',
        ];
    }

    /**
     * Get a description for internal Laravel operations
     */
    protected static function getInternalDescription(string $sql): string
    {
        $sql = strtolower($sql);

        if (str_contains($sql, 'sessions')) {
            return 'Session Management';
        }
        if (str_contains($sql, 'cache')) {
            return 'Cache Operations';
        }
        if (str_contains($sql, 'jobs') || str_contains($sql, 'job_batches')) {
            return 'Queue Management';
        }
        if (str_contains($sql, 'migrations')) {
            return 'Database Migrations';
        }
        if (str_contains($sql, 'failed_jobs')) {
            return 'Failed Jobs Tracking';
        }
        if (str_contains($sql, 'password_reset')) {
            return 'Password Reset';
        }
        if (str_contains($sql, 'telescope')) {
            return 'Laravel Telescope';
        }
        if (str_contains($sql, 'pulse')) {
            return 'Laravel Pulse';
        }

        return 'Framework Operations';
    }

    /**
     * Format a frame into a readable description
     */
    protected static function formatFrameDescription(array $frame): string
    {
        $parts = [];

        if (! empty($frame['class']) && ! empty($frame['function'])) {
            $parts[] = $frame['class'] . '::' . $frame['function'] . '()';
        } elseif (! empty($frame['function'])) {
            $parts[] = $frame['function'] . '()';
        }

        if (! empty($frame['file'])) {
            $file = basename($frame['file']);
            if (! empty($frame['line'])) {
                $parts[] = $file . ':' . $frame['line'];
            } else {
                $parts[] = $file;
            }
        }

        return implode(' - ', $parts) ?: 'Unknown location';
    }

    /**
     * Get a short file path (relative to project root)
     */
    public static function getShortPath(string $file): string
    {
        // Try to find common base paths
        $basePaths = [
            '/app/',
            '/routes/',
            '/database/',
            '/resources/',
        ];

        foreach ($basePaths as $basePath) {
            if (str_contains($file, $basePath)) {
                $pos = strpos($file, $basePath);
                return substr($file, $pos + 1);
            }
        }

        // If in vendor, show package name
        if (str_contains($file, '/vendor/')) {
            $parts = explode('/vendor/', $file);
            if (count($parts) > 1) {
                $vendorPath = explode('/', $parts[1], 3);
                if (count($vendorPath) >= 2) {
                    return 'vendor/' . $vendorPath[0] . '/' . $vendorPath[1];
                }
            }
        }

        // Just return basename if we can't shorten it nicely
        return basename(dirname($file)) . '/' . basename($file);
    }
}
