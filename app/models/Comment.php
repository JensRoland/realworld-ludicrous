<?php

namespace App\Models;

use App\Lib\Database;

class Comment
{
    public static function create(string $body, int $articleId, int $authorId): ?array
    {
        $db = Database::getConnection();

        $db->insert('comments', [
            'body' => $body,
            'article_id' => $articleId,
            'author_id' => $authorId
        ]);

        $commentId = $db->lastInsertId();
        return self::findById($commentId);
    }

    public static function delete(int $id, int $authorId): bool
    {
        $db = Database::getConnection();
        return $db->delete('comments', [
            'id' => $id,
            'author_id' => $authorId
        ]) > 0;
    }

    public static function findByArticle(int $articleId): array
    {
        $db = Database::getConnection();
        $sql = "
            SELECT c.*, u.username as author_username, u.image as author_image, u.bio as author_bio
            FROM comments c
            JOIN users u ON c.author_id = u.id
            WHERE c.article_id = ?
            ORDER BY c.created_at DESC
        ";
        return $db->fetchAllAssociative($sql, [$articleId]);
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $sql = "
            SELECT c.*, u.username as author_username, u.image as author_image, u.bio as author_bio
            FROM comments c
            JOIN users u ON c.author_id = u.id
            WHERE c.id = ?
        ";
        $comment = $db->fetchAssociative($sql, [$id]);
        return $comment ?: null;
    }
}
