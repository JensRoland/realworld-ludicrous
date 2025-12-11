<?php

use App\Lib\Auth;
use App\Lib\View;
use App\Models\Article;
use App\Models\Comment;

$article = Article::findBySlug($slug);

if (!$article) {
    http_response_code(404);
    View::renderLayout('404', ['path' => $_SERVER['REQUEST_URI'] ?? '']);
    return;
}

$comments = Comment::findByArticle($article['id']);
$isFavorited = false;

if (Auth::check()) {
    $isFavorited = Article::isFavorited(Auth::userId(), $article['id']);
}

View::renderLayout('article', [
    'article' => $article,
    'comments' => $comments,
    'isFavorited' => $isFavorited
]);
