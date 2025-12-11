<?php

use App\Lib\Auth;
use App\Lib\View;
use App\Models\Article;

$tag = $_GET['tag'] ?? null;
$feed = $_GET['feed'] ?? 'global';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

if ($feed === 'your' && Auth::check()) {
    $articles = Article::getFeed(Auth::userId(), $limit, $offset);
    $totalItems = Article::getFeedCount(Auth::userId());
} else {
    $articles = Article::getGlobalFeed($limit, $offset, $tag);
    $totalItems = Article::getGlobalFeedCount($tag);
}

$tags = Article::getAllTags(3);

View::renderLayout('home', [
    'articles' => $articles,
    'tags' => $tags,
    'activeTag' => $tag,
    'activeFeed' => $feed,
    'page' => $page,
    'limit' => $limit,
    'totalItems' => $totalItems
]);
