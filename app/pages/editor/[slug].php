<?php

use App\Lib\Auth;
use App\Lib\View;
use App\Models\Article;

Auth::require();

match ($request->method) {
    'GET' => showEditPage($slug),
    'POST' => updateArticle($slug),
    default => abort(405),
};

function showEditPage(string $slug): void
{
    $article = Article::findBySlug($slug);

    if (!$article) {
        http_response_code(404);
        View::renderLayout('404', ['path' => $_SERVER['REQUEST_URI'] ?? '']);
        return;
    }

    if ($article['author_id'] != Auth::userId()) {
        http_response_code(403);
        echo "Forbidden";
        return;
    }

    View::renderLayout('editor', ['article' => $article]);
}

function updateArticle(string $slug): void
{
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

function abort(int $code): void
{
    http_response_code($code);
    echo "Method not allowed";
}
