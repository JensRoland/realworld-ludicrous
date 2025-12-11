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
        $qb = $db->createQueryBuilder();

        return $qb->select(
            'c.*',
            'u.username as author_username',
            'u.image as author_image',
            'u.bio as author_bio'
        )
            ->from('comments', 'c')
            ->join('c', 'users', 'u', 'c.author_id = u.id')
            ->where('c.article_id = :articleId')
            ->orderBy('c.created_at', 'DESC')
            ->setParameter('articleId', $articleId)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $qb = $db->createQueryBuilder();

        $comment = $qb->select(
            'c.*',
            'u.username as author_username',
            'u.image as author_image',
            'u.bio as author_bio'
        )
            ->from('comments', 'c')
            ->join('c', 'users', 'u', 'c.author_id = u.id')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchAssociative();

        return $comment ?: null;
    }
}
