<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\Auth;
use App\Models\Comment;
use App\Models\Article;

class CommentController {
    public function create(string $slug): void {
        if (!Auth::check()) {
            header('HX-Redirect: /login');
            exit;
        }

        $article = Article::findBySlug($slug);
        if (!$article) {
            http_response_code(404);
            echo "Article not found";
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
            $this->renderComment($comment);
        } else {
            http_response_code(500);
            echo "Failed to create comment";
        }
    }

    public function delete(string $slug, string $commentId): void {
        if (!Auth::check()) {
            header('HX-Redirect: /login');
            exit;
        }

        if (Comment::delete((int)$commentId, Auth::userId())) {
            // Return empty string to remove element from DOM
            echo "";
        } else {
            http_response_code(403);
            echo "Failed to delete comment";
        }
    }

    private function renderComment(array $comment): void {
        ?>
        <div class="card">
            <div class="card-block">
                <p class="card-text"><?= htmlspecialchars($comment['body']) ?></p>
            </div>
            <div class="card-footer">
                <a href="/profile/<?= htmlspecialchars($comment['author_username']) ?>" class="comment-author">
                    <img src="<?= $comment['author_image'] ?: '/img/smiley-cyrus.jpg' ?>" class="comment-author-img" />
                </a>
                &nbsp;
                <a href="/profile/<?= htmlspecialchars($comment['author_username']) ?>" class="comment-author"><?= htmlspecialchars($comment['author_username']) ?></a>
                <span class="date-posted"><?= date('F jS', strtotime($comment['created_at'])) ?></span>
                <?php if (\App\Core\Auth::check() && \App\Core\Auth::userId() == $comment['author_id']): ?>
                    <span class="mod-options">
                        <i class="ion-trash-a"
                           hx-delete="/article/<?= htmlspecialchars($_GET['slug'] ?? '') ?>/comment/<?= $comment['id'] ?>"
                           hx-target="closest .card"
                           hx-swap="outerHTML"></i>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}