<?php

namespace App\Components\Pagination;

use App\Lib\View;
use Nette\Utils\Paginator;

/**
 * Render a pagination component with page numbers.
 *
 * @param int $page Current page (1-indexed)
 * @param int $itemsPerPage Items per page
 * @param int $totalItems Total number of items
 * @param string $baseUrl Base URL for pagination links
 * @param array $queryParams Additional query parameters to preserve
 */
function render(int $page, int $itemsPerPage, int $totalItems, string $baseUrl = '/', array $queryParams = []): void
{
    $paginator = new Paginator();
    $paginator->setPage($page);
    $paginator->setItemsPerPage($itemsPerPage);
    $paginator->setItemCount($totalItems);

    // Don't render if only one page
    if ($paginator->getPageCount() <= 1) {
        return;
    }

    // Build URL for a given page
    $buildUrl = function (int $pageNum) use ($baseUrl, $queryParams) {
        $params = array_filter(
            array_merge($queryParams, ['page' => $pageNum]),
            fn($v) => $v !== null && $v !== '' && $v !== 1
        );
        $query = http_build_query($params);
        return $baseUrl . ($query ? '?' . $query : '');
    };

    // Determine which page numbers to show
    $currentPage = $paginator->getPage();
    $lastPage = $paginator->getPageCount();
    $pages = buildPageRange($currentPage, $lastPage);

    $props = [
        'currentPage' => $currentPage,
        'lastPage' => $lastPage,
        'isFirst' => $paginator->isFirst(),
        'isLast' => $paginator->isLast(),
        'previousUrl' => !$paginator->isFirst() ? $buildUrl($currentPage - 1) : '',
        'nextUrl' => !$paginator->isLast() ? $buildUrl($currentPage + 1) : '',
        'pages' => array_map(fn($p) => [
            'number' => $p,
            'url' => $p !== null ? $buildUrl($p) : '',
            'isCurrent' => $p === $currentPage,
            'isEllipsis' => $p === null,
        ], $pages),
    ];

    View::component(__DIR__ . '/template.latte', $props);
}

/**
 * Build an array of page numbers to display, with nulls representing ellipses.
 * Pattern: [1] [2] [3] ... [last-2] [last-1] [last]
 * Shows first 3 pages, ellipsis, and last 3 pages (adjusts based on current page position).
 * Inspired by https://github.com/TonyMckes/conduit-realworld-example-app
 */
function buildPageRange(int $current, int $last): array
{
    if ($last <= 7) {
        return range(1, $last);
    }

    $pages = [];

    // Determine the range around current page (current and neighbors)
    $startRange = max(1, $current - 1);
    $endRange = min($last, $current + 1);

    // Ensure we show at least first 3 pages if current is near start
    if ($startRange <= 3) {
        $startRange = 1;
        $endRange = max($endRange, 3);
    }

    // Ensure we show at least last 3 pages if current is near end
    if ($endRange >= $last - 2) {
        $endRange = $last;
        $startRange = min($startRange, $last - 2);
    }

    // Add pages from start range
    for ($i = $startRange; $i <= $endRange; $i++) {
        $pages[] = $i;
    }

    // If there's a gap before the last 3 pages, add ellipsis and last pages
    if ($endRange < $last - 2) {
        $pages[] = null; // ellipsis
        $pages[] = $last - 2;
        $pages[] = $last - 1;
        $pages[] = $last;
    }

    // If there's a gap after the first 3 pages, prepend first pages and ellipsis
    if ($startRange > 3) {
        array_unshift($pages, null); // ellipsis
        array_unshift($pages, 3);
        array_unshift($pages, 2);
        array_unshift($pages, 1);
    }

    return $pages;
}
