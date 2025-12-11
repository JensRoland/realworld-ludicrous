<?php

namespace App\Components\Comment;

use App\Lib\Auth;
use App\Lib\View;

/**
 * Render a comment card.
 *
 * @param array $comment Comment data with author_username, author_image, body, created_at, author_id, id
 * @param string $articleSlug The article slug (for delete action)
 */
function render(array $comment, string $articleSlug): void
{
    $authorImage = $comment['author_image'] ?: '/img/smiley-cyrus.avif';

    $props = [
        'id' => $comment['id'],
        'body' => $comment['body'],
        'authorUsername' => $comment['author_username'],
        'authorImage' => $authorImage,
        'authorImageThumb' => View::thumbnail($authorImage),
        'date' => date('F jS', strtotime($comment['created_at'])),
        'articleSlug' => $articleSlug,
        'canDelete' => Auth::isUser($comment['author_id']),
    ];

    View::component(__DIR__ . '/template.latte', $props);
}
