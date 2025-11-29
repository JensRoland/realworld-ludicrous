<?php

use App\Lib\Auth;
use App\Models\Article;

if (!Auth::check()) {
    header('Location: /login');
    exit;
}

$article = Article::findBySlug($slug);
if ($article) {
    Article::favorite(Auth::userId(), $article['id']);
    $article['favoritesCount'] = Article::favoritesCount($article['id']);
}

$alignRight = isset($_GET['align']) && $_GET['align'] === 'right';
$compact = isset($_GET['variant']) && $_GET['variant'] === 'compact';
\App\Components\FavoriteButton\render($article, true, $alignRight, $compact);
