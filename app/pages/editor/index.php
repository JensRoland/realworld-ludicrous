<?php

use App\Lib\Auth;
use App\Lib\View;
use App\Models\Article;

Auth::require();

match ($request->method) {
    'GET' => showEditor(),
    'POST' => createArticle(),
    default => abort(405),
};

function showEditor(): void
{
    View::renderLayout('editor');
}

function createArticle(): void
{
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

function abort(int $code): void
{
    http_response_code($code);
    echo "Method not allowed";
}
