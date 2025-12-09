<?php

namespace App\Components\ArticlePreview;

use App\Lib\Auth;
use App\Lib\View;
use App\Models\Article;

/**
 * Render an article preview card.
 *
 * @param array $article Article data
 */
function render(array $article): void
{
    $isFavorited = false;
    if (Auth::check()) {
        $isFavorited = Article::isFavorited(Auth::userId(), $article['id']);
    }

    $authorImage = $article['author_image'] ?: '/img/smiley-cyrus.avif';

    $props = [
        'slug' => $article['slug'],
        'title' => $article['title'],
        'description' => $article['description'],
        'authorUsername' => $article['author_username'],
        'authorImage' => $authorImage,
        'authorImageThumb' => View::thumbnail($authorImage),
        'date' => date('F jS', strtotime($article['created_at'])),
        'tagList' => $article['tagList'] ?? [],
        'article' => $article,
        'isFavorited' => $isFavorited,
    ];

    View::component(__DIR__ . '/template.php', $props);
}
