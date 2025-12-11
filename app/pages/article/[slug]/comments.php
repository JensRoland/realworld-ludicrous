<?php

use App\Lib\Auth;
use App\Models\Article;
use App\Models\Comment;

if (!Auth::check()) {
    header('Location: /login');
    exit;
}

$article = Article::findBySlug($slug);
if (!$article) {
    http_response_code(404);
    echo "Article not found"; // API endpoint - plain text is appropriate
    return;
}

$body = $_POST['body'] ?? '';
if (empty($body)) {
    http_response_code(400);
    echo "Comment body is required";
    return;
}

$comment = Comment::create($body, $article['id'], Auth::userId());

if ($comment) {
    \App\Components\Comment\render($comment, $slug);
} else {
    http_response_code(500);
    echo "Failed to create comment";
}
