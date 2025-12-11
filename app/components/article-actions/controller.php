<?php

namespace App\Components\ArticleActions;

use App\Lib\Auth;
use App\Lib\View;
use App\Models\Article;
use App\Models\User;

/**
 * Render article action buttons (Edit/Delete for author, Follow/Favorite for others).
 *
 * @param array $article Article data
 * @param bool|null $isFavorited Whether current user has favorited (null to auto-detect)
 * @param bool|null $isFollowing Whether current user is following author (null to auto-detect)
 */
function render(array $article, ?bool $isFavorited = null, ?bool $isFollowing = null): void
{
    $isAuthor = Auth::isUser($article['author_id']);
    $isLoggedIn = Auth::check();

    // Auto-detect following status if not provided
    if ($isFollowing === null && $isLoggedIn && !$isAuthor) {
        $isFollowing = User::isFollowing(Auth::userId(), $article['author_id']);
    }

    // Get favorites count if not in article data
    $favoritesCount = $article['favoritesCount'] ?? Article::favoritesCount($article['id']);

    $props = [
        'article' => $article,
        'isAuthor' => $isAuthor,
        'isLoggedIn' => $isLoggedIn,
        'isFavorited' => $isFavorited ?? false,
        'isFollowing' => $isFollowing ?? false,
        'favoritesCount' => $favoritesCount,
    ];

    View::component(__DIR__ . '/template.latte', $props);
}
