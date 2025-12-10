<?php

namespace App\Components\ArticleList;

use App\Lib\View;

/**
 * Render a list of article previews with empty state.
 *
 * @param array $articles Array of article data
 * @param string $emptyMessage Message to show when no articles
 */
function render(array $articles, string $emptyMessage = 'No articles are here... yet.'): void
{
    $props = [
        'articles' => $articles,
        'emptyMessage' => $emptyMessage,
    ];

    View::component(__DIR__ . '/template.latte', $props);
}
