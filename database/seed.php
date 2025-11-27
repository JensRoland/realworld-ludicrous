#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\Seeder;

// Parse command-line arguments
$dataFile = null;
if ($argc > 1) {
    $dataFile = $argv[1];
    if (!is_readable($dataFile)) {
        echo "Error: Cannot read data file: {$dataFile}\n";
        exit(1);
    }
    echo "Using custom data file: {$dataFile}\n";
}

echo "Running database seeder...\n\n";

try {
    $summary = Seeder::seed($dataFile);

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
