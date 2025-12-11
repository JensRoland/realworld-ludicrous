<?php

namespace App\Components\TagInput;

use App\Lib\View;

/**
 * Render the tag input component.
 *
 * @param string $name Form field name
 * @param array $tags Initial tags array
 */
function render(string $name = 'tags', array $tags = []): void
{
    $props = [
        'name' => $name,
        'value' => implode(',', $tags),
    ];

    View::component(__DIR__ . '/template.latte', $props);
}
