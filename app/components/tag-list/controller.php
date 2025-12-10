<?php

namespace App\Components\TagList;

use App\Lib\View;

/**
 * Render a tag list component.
 *
 * @param array $tags Array of tag strings
 * @param string $variant Display variant: 'sidebar' (clickable pills) or 'inline' (read-only outline)
 */
function render(array $tags, string $variant = 'sidebar'): void
{
    $props = [
        'tags' => $tags,
        'variant' => $variant,
    ];

    View::component(__DIR__ . '/template.latte', $props);
}
