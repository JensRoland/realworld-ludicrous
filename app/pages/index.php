<?php

use App\Lib\Auth;
use App\Lib\View;
use App\Models\Article;

$tag = $_GET['tag'] ?? null;
$feed = $_GET['feed'] ?? 'global';
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = 20;

if ($feed === 'your' && Auth::check()) {
    $articles = Article::getFeed(Auth::userId(), $limit, $offset);
} else {
    $articles = Article::getGlobalFeed($limit, $offset, $tag);
}

$tags = Article::getAllTags(3);

View::renderLayout('home', [
    'articles' => $articles,
    'tags' => $tags,
    'activeTag' => $tag,
    'activeFeed' => $feed,
    'offset' => $offset,
    'limit' => $limit
]);
