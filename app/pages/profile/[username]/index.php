<?php

use App\Lib\Auth;
use App\Lib\View;
use App\Models\User;
use App\Models\Article;

$profile = User::findByUsername($username);

if (!$profile) {
    http_response_code(404);
    View::renderLayout('404', ['path' => $_SERVER['REQUEST_URI'] ?? '']);
    return;
}

$activeTab = (isset($_GET['tab']) && $_GET['tab'] === 'favorites') ? 'favorites' : 'my';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

if ($activeTab === 'favorites') {
    $articles = Article::getFavoritedByUser((int)$profile['id'], $limit, $offset);
    $totalItems = Article::getFavoritedByUserCount((int)$profile['id']);
} else {
    $articles = Article::getGlobalFeed($limit, $offset, null, (int)$profile['id']);
    $totalItems = Article::getGlobalFeedCount(null, (int)$profile['id']);
}

$isFollowing = false;

if (Auth::check()) {
    $isFollowing = User::isFollowing(Auth::userId(), $profile['id']);
}

View::renderLayout('profile', [
    'profile' => $profile,
    'articles' => $articles,
    'isFollowing' => $isFollowing,
    'activeTab' => $activeTab,
    'page' => $page,
    'limit' => $limit,
    'totalItems' => $totalItems
]);
