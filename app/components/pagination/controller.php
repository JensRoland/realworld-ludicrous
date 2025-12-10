<?php

namespace App\Components\Pagination;

use App\Lib\View;

/**
 * Render a pagination component with Previous/Next links.
 *
 * @param int $offset Current offset
 * @param int $limit Items per page
 * @param int $itemCount Number of items on current page
 * @param string $baseUrl Base URL for pagination links
 * @param array $queryParams Additional query parameters to preserve
 */
function render(int $offset, int $limit, int $itemCount, string $baseUrl = '/', array $queryParams = []): void
{
    $hasPrevious = $offset > 0;
    $hasNext = $itemCount >= $limit;

    if (!$hasPrevious && !$hasNext) {
        return;
    }

    // Build query strings
    $buildUrl = function (int $newOffset) use ($baseUrl, $queryParams) {
        $params = array_filter(array_merge($queryParams, ['offset' => $newOffset]), fn($v) => $v !== null && $v !== '' && $v !== 0);
        $query = http_build_query($params);
        return $baseUrl . ($query ? '?' . $query : '');
    };

    $props = [
        'hasPrevious' => $hasPrevious,
        'hasNext' => $hasNext,
        'previousUrl' => $hasPrevious ? $buildUrl(max(0, $offset - $limit)) : '',
        'nextUrl' => $hasNext ? $buildUrl($offset + $limit) : '',
    ];

    View::component(__DIR__ . '/template.latte', $props);
}
