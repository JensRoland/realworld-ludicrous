<?php

namespace App\Models;

use App\Core\Database;

class Article {
    private static array $tagsCache = [];

    public static function create(string $title, string $description, string $body, array $tags, int $authorId): ?string {
        $db = Database::getConnection();
        $slug = self::slugify($title);

        // Ensure unique slug
        $originalSlug = $slug;
        $count = 1;
        while (self::findBySlug($slug)) {
            $slug = $originalSlug . '-' . $count++;
        }

        $db->beginTransaction();
        try {
            $db->insert('articles', [
                'slug' => $slug,
                'title' => $title,
                'description' => $description,
                'body' => $body,
                'author_id' => $authorId
            ]);
            $articleId = $db->lastInsertId();

            foreach ($tags as $tagName) {
                $tagName = trim($tagName);
                if (empty($tagName)) continue;

                // Find or create tag
                $tagId = $db->fetchOne("SELECT id FROM tags WHERE name = ?", [$tagName]);

                if (!$tagId) {
                    $db->insert('tags', ['name' => $tagName]);
                    $tagId = $db->lastInsertId();
                }

                // Link tag to article
                $db->insert('article_tags', [
                    'article_id' => $articleId,
                    'tag_id' => $tagId
                ]);
            }

            $db->commit();
            return $slug;
        } catch (\Exception $e) {
            $db->rollBack();
            return null;
        }
    }

    public static function update(string $slug, string $title, string $description, string $body, int $authorId, ?array $tags = null): ?string {
        $db = Database::getConnection();

        // Verify ownership
        $article = self::findBySlug($slug);
        if (!$article || $article['author_id'] != $authorId) {
            return null;
        }
        $articleId = (int)$article['id'];

        $db->beginTransaction();
        try {
            $newSlug = $slug;
            if ($title !== $article['title']) {
                $newSlug = self::slugify($title);
                // Ensure unique slug if changed
                if ($newSlug !== $slug) {
                    $originalSlug = $newSlug;
                    $count = 1;
                    while (self::findBySlug($newSlug)) {
                        $newSlug = $originalSlug . '-' . $count++;
                    }
                }
            }

            $db->executeStatement(
                "UPDATE articles SET slug = ?, title = ?, description = ?, body = ?, updated_at = CURRENT_TIMESTAMP WHERE slug = ?",
                [$newSlug, $title, $description, $body, $slug]
            );

            // Update tags if provided
            if ($tags !== null) {
                // Clear existing links
                $db->delete('article_tags', ['article_id' => $articleId]);

                foreach ($tags as $tagName) {
                    $tagName = trim((string)$tagName);
                    if ($tagName === '') {
                        continue;
                    }
                    // Find or create tag
                    $tagId = $db->fetchOne("SELECT id FROM tags WHERE name = ?", [$tagName]);

                    if (!$tagId) {
                        $db->insert('tags', ['name' => $tagName]);
                        $tagId = $db->lastInsertId();
                    }

                    // Link tag to article (ignore duplicates)
                    try {
                        $db->insert('article_tags', [
                            'article_id' => $articleId,
                            'tag_id' => $tagId
                        ]);
                    } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                        // Already exists, that's ok
                    }
                }
            }

            $db->commit();
            return $newSlug;
        } catch (\Exception $e) {
            $db->rollBack();
            return null;
        }
    }

    public static function delete(string $slug, int $authorId): bool {
        $db = Database::getConnection();
        return $db->executeStatement(
            "DELETE FROM articles WHERE slug = ? AND author_id = ?",
            [$slug, $authorId]
        ) > 0;
    }

    public static function favorite(int $userId, int $articleId): bool {
        $db = Database::getConnection();
        try {
            $db->insert('favorites', [
                'user_id' => $userId,
                'article_id' => $articleId
            ]);
            return true;
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            // Already favorited, that's ok
            return true;
        }
    }

    public static function unfavorite(int $userId, int $articleId): bool {
        $db = Database::getConnection();
        return $db->delete('favorites', [
            'user_id' => $userId,
            'article_id' => $articleId
        ]) > 0;
    }

    public static function isFavorited(int $userId, int $articleId): bool {
        $db = Database::getConnection();
        $result = $db->fetchOne(
            "SELECT 1 FROM favorites WHERE user_id = ? AND article_id = ?",
            [$userId, $articleId]
        );
        return (bool) $result;
    }

    public static function favoritesCount(int $articleId): int {
        $db = Database::getConnection();
        return (int) $db->fetchOne(
            "SELECT COUNT(*) FROM favorites WHERE article_id = ?",
            [$articleId]
        );
    }

    public static function getGlobalFeed(int $limit = 20, int $offset = 0, ?string $tag = null, ?int $authorId = null): array {
        $db = Database::getConnection();

        // Get the aggregation function based on database platform
        $platform = $db->getDatabasePlatform();
        $groupConcat = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform
            ? "STRING_AGG(t.name, ',')"
            : "GROUP_CONCAT(t.name)";

        $sql = "
            SELECT a.*, u.username as author_username, u.image as author_image,
            (SELECT {$groupConcat} FROM tags t JOIN article_tags at ON t.id = at.tag_id WHERE at.article_id = a.id) as tagList,
            (SELECT COUNT(*) FROM favorites f WHERE f.article_id = a.id) as favoritesCount
            FROM articles a
            JOIN users u ON a.author_id = u.id
        ";

        $params = [];
        $where = [];

        if ($tag) {
            $where[] = "a.id IN (SELECT at.article_id FROM article_tags at JOIN tags t ON at.tag_id = t.id WHERE t.name = ?)";
            $params[] = $tag;
        }

        if ($authorId) {
            $where[] = "a.author_id = ?";
            $params[] = $authorId;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $articles = $db->fetchAllAssociative($sql, $params);

        foreach ($articles as &$article) {
            $article['tagList'] = $article['tagList'] ? explode(',', $article['tagList']) : [];
        }

        return $articles;
    }

    public static function getFeed(int $userId, int $limit = 20, int $offset = 0): array {
        $db = Database::getConnection();

        // Get the aggregation function based on database platform
        $platform = $db->getDatabasePlatform();
        $groupConcat = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform
            ? "STRING_AGG(t.name, ',')"
            : "GROUP_CONCAT(t.name)";

        $sql = "
            SELECT a.*, u.username as author_username, u.image as author_image,
            (SELECT {$groupConcat} FROM tags t JOIN article_tags at ON t.id = at.tag_id WHERE at.article_id = a.id) as tagList,
            (SELECT COUNT(*) FROM favorites f WHERE f.article_id = a.id) as favoritesCount
            FROM articles a
            JOIN users u ON a.author_id = u.id
            JOIN follows f ON f.followed_id = a.author_id
            WHERE f.follower_id = ?
            ORDER BY a.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $articles = $db->fetchAllAssociative($sql, [$userId, $limit, $offset]);

        foreach ($articles as &$article) {
            $article['tagList'] = $article['tagList'] ? explode(',', $article['tagList']) : [];
        }

        return $articles;
    }

    public static function getAllTags(int $minArticleCount = 1): array {
        // Simple in-memory cache for tags (they don't change often during a request)
        $cacheKey = "tags_{$minArticleCount}";
        if (isset(self::$tagsCache[$cacheKey])) {
            return self::$tagsCache[$cacheKey];
        }

        $db = Database::getConnection();
        // Note: SQLite has issues with parameter binding in HAVING clauses with Doctrine DBAL
        // Using int cast for safety since this is an integer parameter
        $minCount = (int) $minArticleCount;
        $sql = "
            SELECT t.name
            FROM tags t
            JOIN article_tags at ON t.id = at.tag_id
            GROUP BY t.id, t.name
            HAVING COUNT(DISTINCT at.article_id) >= {$minCount}
            ORDER BY COUNT(DISTINCT at.article_id) DESC, t.name ASC
        ";
        $result = $db->fetchAllAssociative($sql);
        $tags = array_column($result, 'name');

        self::$tagsCache[$cacheKey] = $tags;
        return $tags;
    }

    public static function getFavoritedByUser(int $userId, int $limit = 20, int $offset = 0): array {
        $db = Database::getConnection();

        // Get the aggregation function based on database platform
        $platform = $db->getDatabasePlatform();
        $groupConcat = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform
            ? "STRING_AGG(t.name, ',')"
            : "GROUP_CONCAT(t.name)";

        $sql = "
            SELECT a.*, u.username as author_username, u.image as author_image,
                   (SELECT {$groupConcat} FROM tags t
                        JOIN article_tags at ON t.id = at.tag_id
                    WHERE at.article_id = a.id) as tagList,
                   (SELECT COUNT(*) FROM favorites f2 WHERE f2.article_id = a.id) as favoritesCount
            FROM favorites f
            JOIN articles a ON f.article_id = a.id
            JOIN users u ON a.author_id = u.id
            WHERE f.user_id = ?
            ORDER BY a.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $articles = $db->fetchAllAssociative($sql, [$userId, $limit, $offset]);

        foreach ($articles as &$article) {
            $article['tagList'] = $article['tagList'] ? explode(',', $article['tagList']) : [];
        }
        return $articles;
    }

    public static function findBySlug(string $slug): ?array {
        $db = Database::getConnection();

        // Get the aggregation function based on database platform
        $platform = $db->getDatabasePlatform();
        $groupConcat = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform
            ? "STRING_AGG(t.name, ',')"
            : "GROUP_CONCAT(t.name)";

        $sql = "
            SELECT a.*, u.username as author_username, u.image as author_image, u.bio as author_bio,
            (SELECT {$groupConcat} FROM tags t JOIN article_tags at ON t.id = at.tag_id WHERE at.article_id = a.id) as tagList,
            (SELECT COUNT(*) FROM favorites f WHERE f.article_id = a.id) as favoritesCount
            FROM articles a
            JOIN users u ON a.author_id = u.id
            WHERE a.slug = ?
        ";

        $article = $db->fetchAssociative($sql, [$slug]);

        if ($article) {
            $article['tagList'] = $article['tagList'] ? explode(',', $article['tagList']) : [];
        }

        return $article ?: null;
    }

    private static function slugify(string $text): string {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = strtolower($text);
        return empty($text) ? 'n-a' : $text;
    }
}
