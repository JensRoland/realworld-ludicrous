<?php

namespace App\Components\CommentList;

use App\Lib\View;

/**
 * Render a list of comments.
 *
 * @param array $comments Array of comment data
 * @param string $articleSlug Article slug for delete actions
 */
function render(array $comments, string $articleSlug): void
{
    $props = [
        'comments' => $comments,
        'articleSlug' => $articleSlug,
    ];

    View::component(__DIR__ . '/template.latte', $props);
}
