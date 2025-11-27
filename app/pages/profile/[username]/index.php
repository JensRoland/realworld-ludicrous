<?php

use App\Lib\Auth;
use App\Lib\View;
use App\Models\User;
use App\Models\Article;

$profile = User::findByUsername($username);

if (!$profile) {
    http_response_code(404);
    echo "Profile not found";
    return;
}

$activeTab = (isset($_GET['tab']) && $_GET['tab'] === 'favorites') ? 'favorites' : 'my';

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = 20;

if ($activeTab === 'favorites') {
    $articles = Article::getFavoritedByUser((int)$profile['id'], $limit, $offset);
} else {
    $articles = Article::getGlobalFeed($limit, $offset, null, (int)$profile['id']);
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
    'offset' => $offset,
    'limit' => $limit
]);
