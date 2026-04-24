#!/usr/bin/env php
<?php

require_once __DIR__ . '/../app/vendor/autoload.php';

use App\Services\Seeder;

// Parse command-line arguments
$dataFile = null;
$reset = false;

$args = array_slice($argv, 1);
foreach ($args as $arg) {
    if ($arg === '--reset') {
        $reset = true;
        continue;
    }
    if (!is_readable($arg)) {
        echo "Error: Cannot read data file: {$arg}\n";
        exit(1);
    }
    $dataFile = $arg;
    echo "Using custom data file: {$dataFile}\n";
}

if ($reset) {
    echo "Reset mode: truncating all tables before seeding.\n";
}

echo "Running database seeder...\n\n";

try {
    $summary = Seeder::seed($dataFile, $reset);

    echo "Seeding completed successfully!\n\n";
    echo "Summary:\n";
    echo "--------\n";
    echo "Users:     {$summary['users_before']} -> {$summary['users_after']} (+{$summary['users_added']})\n";
    echo "Articles:  {$summary['articles_before']} -> {$summary['articles_after']} (+{$summary['articles_added']})\n";
    echo "Tags:      {$summary['tags_before']} -> {$summary['tags_after']} (+{$summary['tags_added']})\n";
    echo "Comments:  {$summary['comments_before']} -> {$summary['comments_after']} (+{$summary['comments_added']})\n";
    echo "Favorites: {$summary['favorites_before']} -> {$summary['favorites_after']} (+{$summary['favorites_added']})\n";
    echo "Follows:   {$summary['follows_before']} -> {$summary['follows_after']} (+{$summary['follows_added']})\n";

    exit(0);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
