<?php

namespace App\Components\ArticleMeta;

use App\Lib\View;

/**
 * Render article meta info (author avatar, name, date).
 *
 * @param array $article Article data with author_username, author_image, created_at
 */
function render(array $article): void
{
    $authorImage = $article['author_image'] ?: '/img/smiley-cyrus.avif';

    $props = [
        'authorUsername' => $article['author_username'],
        'authorImage' => $authorImage,
        'authorImageThumb' => View::thumbnail($authorImage),
        'date' => date('F jS', strtotime($article['created_at'])),
    ];

    View::component(__DIR__ . '/template.php', $props);
}
