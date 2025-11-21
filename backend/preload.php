<?php
/**
 * OPcache Preload Script
 *
 * This script loads all application classes into shared memory at server startup.
 * Result: Zero ClassLoader overhead, faster cold starts.
 *
 * Note: Preloading is incompatible with Xdebug in most cases, so this is
 * most useful for production environments.
 */

// Load the Composer autoloader first
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Recursively find all PHP files in a directory
 */
function getPhpFiles(string $directory): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    // Sort to ensure consistent load order (helps with dependencies)
    sort($files);
    return $files;
}

// Preload all application classes
$appFiles = getPhpFiles(__DIR__ . '/src');

// Preload commonly used vendor classes
$vendorFiles = [
    __DIR__ . '/vendor/erusev/parsedown/Parsedown.php',
    __DIR__ . '/vendor/firebase/php-jwt/src/Key.php',
    __DIR__ . '/vendor/firebase/php-jwt/src/JWT.php',
];

// Preload critical Doctrine DBAL classes (excluding console commands)
$doctrineFiles = [];
foreach (getPhpFiles(__DIR__ . '/vendor/doctrine/dbal/src') as $file) {
    // Skip console commands and tools that require Symfony Console
    if (strpos($file, '/Tools/Console/') !== false) {
        continue;
    }
    $doctrineFiles[] = $file;
}

// Add PSR libraries
$doctrineFiles = array_merge(
    $doctrineFiles,
    getPhpFiles(__DIR__ . '/vendor/psr/cache/src'),
    getPhpFiles(__DIR__ . '/vendor/psr/log/src')
);

// Combine all vendor files
$vendorFiles = array_merge($vendorFiles, $doctrineFiles);

// Load all files with error handling
foreach (array_merge($appFiles, $vendorFiles) as $file) {
    if (file_exists($file)) {
        try {
            require_once $file;
        } catch (\Throwable $e) {
            // Skip files that can't be loaded (missing dependencies, etc.)
            // In production, you might want to log this
            error_log("Preload skipped: {$file} - " . $e->getMessage());
        }
    }
}
