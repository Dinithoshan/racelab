<?php

namespace Dinithoshan\Racelab\Engines;

/**
 * Engine that analyzes stack traces and maps queries to meaningful function calls.
 * 
 * This engine identifies the most relevant frame in a stack trace that represents
 * where the query was actually triggered in application code, filtering out
 * framework internals and vendor code.
 */
class QueryTraceMapper
{
    /**
     * Paths to skip when analyzing traces
     */
    protected static array $skipPaths = [
        'racelab',
        'Illuminate/Database/',
        'Illuminate/Events/',
        'Illuminate/Support/',
        'Illuminate/Container/',
    ];

    /**
     * Analyze a stack trace and return the most relevant frame for a query.
     */
    public static function mapQueryToTrace(array $stackFrames): ?array
    {
        if (empty($stackFrames)) {
            return null;
        }

        // First, try to find application code (non-vendor, non-framework)
        $applicationFrame = self::findApplicationFrame($stackFrames);
        if ($applicationFrame !== null) {
            return self::enrichFrame($applicationFrame, 'application');
        }

        // If no application code found, try to find meaningful vendor code
        $vendorFrame = self::findMeaningfulVendorFrame($stackFrames);
        if ($vendorFrame !== null) {
            return self::enrichFrame($vendorFrame, 'vendor');
        }

        // Last resort: return the first frame with file information
        $firstFrame = self::findFirstValidFrame($stackFrames);
        if ($firstFrame !== null) {
            return self::enrichFrame($firstFrame, 'unknown');
        }

        return null;
    }

    /**
     * Find the first frame that represents application code (not vendor, not framework)
     */
    protected static function findApplicationFrame(array $stackFrames): ?array
    {
        foreach ($stackFrames as $frame) {
            if (! self::isValidFrame($frame)) {
                continue;
            }

            $file = $frame['file'] ?? '';

            // Skip vendor code
            if (self::isVendorPath($file)) {
                continue;
            }

            // Skip RaceLab itself
            if (str_contains($file, 'racelab')) {
                continue;
            }

            // Skip Laravel framework paths
            if (self::isFrameworkPath($file)) {
                continue;
            }

            // This is application code!
            return $frame;
        }

        return null;
    }

    /**
     * Find a meaningful vendor frame (not Laravel core, but third-party packages)
     */
    protected static function findMeaningfulVendorFrame(array $stackFrames): ?array
    {
        foreach ($stackFrames as $frame) {
            if (! self::isValidFrame($frame)) {
                continue;
            }

            $file = $frame['file'] ?? '';

            // Must be vendor code
            if (! self::isVendorPath($file)) {
                continue;
            }

            // But not Laravel/Illuminate
            if (str_contains($file, '/illuminate/') || str_contains($file, '\\illuminate\\')) {
                continue;
            }

            // Not RaceLab
            if (str_contains($file, 'racelab')) {
                continue;
            }

            return $frame;
        }

        return null;
    }

    /**
     * Find the first frame with valid file information
     */
    protected static function findFirstValidFrame(array $stackFrames): ?array
    {
        foreach ($stackFrames as $frame) {
            if (self::isValidFrame($frame)) {
                return $frame;
            }
        }

        return null;
    }

    /**
     * Check if a frame is valid (has file information)
     */
    protected static function isValidFrame(array $frame): bool
    {
        return ! empty($frame['file']) && ! self::shouldSkipFrame($frame['file']);
    }

    /**
     * Check if a file path should be skipped
     */
    protected static function shouldSkipFrame(string $path): bool
    {
        foreach (self::$skipPaths as $skipPath) {
            if (str_contains($path, $skipPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a path is vendor code
     */
    protected static function isVendorPath(string $path): bool
    {
        return str_contains($path, '/vendor/') || str_contains($path, '\\vendor\\');
    }

    /**
     * Check if a path is Laravel framework code
     */
    protected static function isFrameworkPath(string $path): bool
    {
        return str_contains($path, 'Illuminate/') || str_contains($path, 'Illuminate\\');
    }

    /**
     * Enrich a frame with additional metadata and formatting
     */
    protected static function enrichFrame(array $frame, string $source): array
    {
        $file = $frame['file'] ?? '';
        $line = $frame['line'] ?? null;
        $class = $frame['class'] ?? null;
        $function = $frame['function'] ?? null;

        // Create a human-readable description
        $description = self::formatFrameDescription($frame);

        // Get a short path for display
        $shortPath = self::getShortPath($file);

        return [
            'file' => $file,
            'line' => $line,
            'class' => $class,
            'function' => $function,
            'is_vendor' => self::isVendorPath($file),
            'source' => $source, // 'application', 'vendor', or 'unknown'
            'description' => $description,
            'short_path' => $shortPath,
        ];
    }

    /**
     * Format a frame into a human-readable description
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
     * Get a short, display-friendly path from a full file path
     */
    protected static function getShortPath(string $file): string
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
                    return 'vendor/' . $vendorPath[0] . '/' . $vendorPath[1] . '/' . basename($file);
                }
            }
        }

        // Just return relative path if we can't shorten it nicely
        return basename(dirname($file)) . '/' . basename($file);
    }

    /**
     * Get the full stack trace as an array of frames
     * 
     * @param array $stackFrames Raw stack frames
     * @return array Array of enriched frames
     */
    public static function getFullTrace(array $stackFrames): array
    {
        $trace = [];

        foreach ($stackFrames as $frame) {
            if (! self::isValidFrame($frame)) {
                continue;
            }

            $trace[] = self::enrichFrame($frame, self::determineFrameSource($frame));
        }

        return $trace;
    }

    /**
     * Determine the source type of a frame
     */
    protected static function determineFrameSource(array $frame): string
    {
        $file = $frame['file'] ?? '';

        if (self::isVendorPath($file)) {
            if (self::isFrameworkPath($file)) {
                return 'framework';
            }
            return 'vendor';
        }

        if (str_contains($file, 'racelab')) {
            return 'racelab';
        }

        return 'application';
    }
}
