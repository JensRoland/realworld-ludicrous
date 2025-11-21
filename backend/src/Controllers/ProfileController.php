<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\Auth;
use App\Models\User;
use App\Models\Article;

class ProfileController {
    public function index(string $username): void {
        $profile = User::findByUsername($username);
        
        if (!$profile) {
            http_response_code(404);
            echo "Profile not found";
            return;
        }

        // Determine active tab: author's own articles or favorited ones
        $activeTab = (isset($_GET['tab']) && $_GET['tab'] === 'favorites') ? 'favorites' : 'my';

        // Pagination
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
    }

    public function follow(string $username): void {
        if (!Auth::check()) {
            header('HX-Redirect: /login');
            exit;
        }

        $profile = User::findByUsername($username);
        if ($profile) {
            if ($profile['id'] === Auth::userId()) {
                http_response_code(400);
                echo "Cannot follow yourself";
                return;
            }
            User::follow(Auth::userId(), $profile['id']);
        }

        $this->renderFollowButton($profile, true);
    }

    public function unfollow(string $username): void {
        if (!Auth::check()) {
            header('HX-Redirect: /login');
            exit;
        }

        $profile = User::findByUsername($username);
        if ($profile) {
            if ($profile['id'] === Auth::userId()) {
                http_response_code(400);
                echo "Cannot unfollow yourself";
                return;
            }
            User::unfollow(Auth::userId(), $profile['id']);
        }

        $this->renderFollowButton($profile, false);
    }

    private function renderFollowButton(array $profile, bool $isFollowing): void {
        ?>
        <button class="btn btn-sm btn-outline-secondary action-btn"
                hx-post="/profile/<?= htmlspecialchars($profile['username']) ?>/<?= $isFollowing ? 'unfollow' : 'follow' ?>"
                hx-target="this"
                hx-swap="outerHTML">
            <i class="ion-plus-round"></i>
            &nbsp;
            <?= $isFollowing ? 'Unfollow' : 'Follow' ?> <?= htmlspecialchars($profile['username']) ?>
        </button>
        <?php
    }
}