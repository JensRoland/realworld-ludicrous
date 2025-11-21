<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\Auth;
use App\Models\Article;
use App\Models\Comment;

class ArticleController {
    public function editorPage(): void {
        Auth::require();
        View::renderLayout('editor');
    }

    public function create(): void {
        Auth::require();

        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $body = $_POST['body'] ?? '';
        $tags = isset($_POST['tags']) ? explode(',', $_POST['tags']) : [];

        if (empty($title) || empty($description) || empty($body)) {
            View::renderLayout('editor', ['error' => 'Title, description, and body are required']);
            return;
        }

        $slug = Article::create($title, $description, $body, $tags, Auth::userId());

        if ($slug) {
            header('Location: /article/' . $slug);
            exit;
        } else {
            View::renderLayout('editor', ['error' => 'Failed to create article']);
        }
    }

    public function editPage(string $slug): void {
        Auth::require();

        $article = Article::findBySlug($slug);

        if (!$article) {
            http_response_code(404);
            echo "Article not found";
            return;
        }

        if ($article['author_id'] != Auth::userId()) {
            http_response_code(403);
            echo "Forbidden";
            return;
        }

        View::renderLayout('editor', ['article' => $article]);
    }

    public function update(string $slug): void {
        Auth::require();

        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $body = $_POST['body'] ?? '';
        $tagsRaw = $_POST['tags'] ?? null;
        $tags = null;
        if ($tagsRaw !== null) {
            $tags = array_values(array_filter(array_map('trim', explode(',', (string)$tagsRaw)), fn($t) => $t !== ''));
        }

        if (empty($title) || empty($description) || empty($body)) {
            $article = Article::findBySlug($slug);
            View::renderLayout('editor', [
                'article' => $article,
                'error' => 'Title, description, and body are required'
            ]);
            return;
        }

        $newSlug = Article::update($slug, $title, $description, $body, Auth::userId(), $tags);

        if ($newSlug) {
            header('Location: /article/' . $newSlug);
            exit;
        } else {
            $article = Article::findBySlug($slug);
            View::renderLayout('editor', [
                'article' => $article,
                'error' => 'Failed to update article'
            ]);
        }
    }

    public function delete(string $slug): void {
        Auth::require();

        if (Article::delete($slug, Auth::userId())) {
            header('Location: /');
            exit;
        } else {
            http_response_code(403);
            echo "Failed to delete article";
        }
    }

    public function view(string $slug): void {
        $article = Article::findBySlug($slug);
        
        if (!$article) {
            http_response_code(404);
            echo "Article not found";
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
    }

    public function favorite(string $slug): void {
        if (!Auth::check()) {
            header('HX-Redirect: /login');
            exit;
        }

        $article = Article::findBySlug($slug);
        if ($article) {
            Article::favorite(Auth::userId(), $article['id']);
            // Recalculate from DB to avoid relying on possibly missing field
            $article['favoritesCount'] = Article::favoritesCount($article['id']);
        }

        $alignRight = isset($_GET['align']) && $_GET['align'] === 'right';
        $compact = isset($_GET['variant']) && $_GET['variant'] === 'compact';
        $this->renderFavoriteButton($article, true, $alignRight, $compact);
    }

    public function unfavorite(string $slug): void {
        if (!Auth::check()) {
            header('HX-Redirect: /login');
            exit;
        }

        $article = Article::findBySlug($slug);
        if ($article) {
            Article::unfavorite(Auth::userId(), $article['id']);
            // Recalculate from DB to avoid relying on possibly missing field
            $article['favoritesCount'] = Article::favoritesCount($article['id']);
        }

        $alignRight = isset($_GET['align']) && $_GET['align'] === 'right';
        $compact = isset($_GET['variant']) && $_GET['variant'] === 'compact';
        $this->renderFavoriteButton($article, false, $alignRight, $compact);
    }

    private function renderFavoriteButton(array $article, bool $isFavorited, bool $alignRight = false, bool $compact = false): void {
        $count = isset($article['favoritesCount']) ? (int)$article['favoritesCount'] : Article::favoritesCount($article['id']);
        $alignClass = $alignRight ? ' pull-xs-right' : '';
        $qs = [];
        if ($alignRight) { $qs[] = 'align=right'; }
        if ($compact) { $qs[] = 'variant=compact'; }
        $qsStr = $qs ? ('?' . implode('&', $qs)) : '';

        if ($compact) {
            ?>
            <button class="btn btn-sm<?= $alignClass ?> <?= $isFavorited ? 'btn-primary' : 'btn-outline-primary' ?>"
                    hx-post="/article/<?= $article['slug'] ?>/<?= $isFavorited ? 'unfavorite' : 'favorite' ?><?= $qsStr ?>"
                    hx-target="this"
                    hx-swap="outerHTML">
                <i class="ion-heart"></i> <?= $count ?>
            </button>
            <?php
            return;
        }
        ?>
        <button class="btn btn-sm<?= $alignClass ?> <?= $isFavorited ? 'btn-primary' : 'btn-outline-primary' ?>"
                hx-post="/article/<?= $article['slug'] ?>/<?= $isFavorited ? 'unfavorite' : 'favorite' ?><?= $qsStr ?>"
                hx-target="this"
                hx-swap="outerHTML">
            <i class="ion-heart"></i>
            &nbsp;
            <?= $isFavorited ? 'Unfavorite' : 'Favorite' ?> Article <span class="counter">(<?= $count ?>)</span>
        </button>
        <?php
    }
}
