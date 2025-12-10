<?php

namespace App\Components\FavoriteButton;

use App\Lib\View;
use App\Models\Article;

/**
 * Render a favorite/unfavorite button for an article.
 *
 * @param array $article Article data with slug, favoritesCount
 * @param bool $isFavorited Whether the current user has favorited this article
 * @param bool $alignRight Add pull-xs-right class
 * @param bool $compact Show compact version (count only, no text)
 */
function render(array $article, bool $isFavorited, bool $alignRight = false, bool $compact = false): void
{
    $count = isset($article['favoritesCount']) ? (int)$article['favoritesCount'] : Article::favoritesCount($article['id']);

    $qs = [];
    if ($alignRight) { $qs[] = 'align=right'; }
    if ($compact) { $qs[] = 'variant=compact'; }

    $props = [
        'slug' => $article['slug'],
        'count' => $count,
        'isFavorited' => $isFavorited,
        'action' => $isFavorited ? 'unfavorite' : 'favorite',
        'queryString' => $qs ? ('?' . implode('&', $qs)) : '',
        'alignClass' => $alignRight ? ' pull-xs-right' : '',
        'buttonClass' => $isFavorited ? 'btn-primary' : 'btn-outline-primary',
        'label' => $isFavorited ? 'Unfavorite' : 'Favorite',
        'compact' => $compact,
    ];

    View::component(__DIR__ . '/template.latte', $props);
}
