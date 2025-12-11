<?php

namespace App\Models;

use App\Lib\Database;
use Doctrine\DBAL\ArrayParameterType;

class Article
{
    private static array $tagsCache = [];

    private static function compileMarkdown(string $body): string
    {
        $parsedown = new \Parsedown();
        $parsedown->setSafeMode(true);
        return $parsedown->text($body);
    }

    /**
     * Fetch tags for a list of article IDs and return as [article_id => [tag1, tag2, ...]]
     */
    private static function fetchTagsForArticles(array $articleIds): array
    {
        if (empty($articleIds)) {
            return [];
        }

        $db = Database::getConnection();
        $qb = $db->createQueryBuilder();

        $rows = $qb->select('at.article_id', 't.name')
            ->from('article_tags', 'at')
            ->join('at', 'tags', 't', 'at.tag_id = t.id')
            ->where($qb->expr()->in('at.article_id', ':ids'))
            ->orderBy('t.name')
            ->setParameter('ids', $articleIds, ArrayParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative();

        $tagsByArticle = [];
        foreach ($rows as $row) {
            $tagsByArticle[$row['article_id']][] = $row['name'];
        }

        return $tagsByArticle;
    }

    /**
     * Attach tagList to each article in the array
     */
    private static function attachTags(array $articles): array
    {
        $articleIds = array_column($articles, 'id');
        $tagsByArticle = self::fetchTagsForArticles($articleIds);

        foreach ($articles as &$article) {
            $article['tagList'] = $tagsByArticle[$article['id']] ?? [];
        }

        return $articles;
    }

    /**
     * Build subquery for favorites count
     */
    private static function favoritesCountSubquery(): string
    {
        $db = Database::getConnection();
        $sub = $db->createQueryBuilder();
        return '(' . $sub->select('COUNT(*)')
            ->from('favorites', 'f')
            ->where('f.article_id = a.id')
            ->getSQL() . ')';
    }

    public static function create(string $title, string $description, string $body, array $tags, int $authorId): ?string
    {
        $db = Database::getConnection();
        $slug = self::slugify($title);

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
                'body_html' => self::compileMarkdown($body),
                'author_id' => $authorId
            ]);
            $articleId = $db->lastInsertId();

            foreach ($tags as $tagName) {
                $tagName = trim($tagName);
                if (empty($tagName)) continue;

                $qb = $db->createQueryBuilder();
                $tagId = $qb->select('id')
                    ->from('tags')
                    ->where('name = :name')
                    ->setParameter('name', $tagName)
                    ->executeQuery()
                    ->fetchOne();

                if (!$tagId) {
                    $db->insert('tags', ['name' => $tagName]);
                    $tagId = $db->lastInsertId();
                }

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

    public static function update(string $slug, string $title, string $description, string $body, int $authorId, ?array $tags = null): ?string
    {
        $db = Database::getConnection();

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
                if ($newSlug !== $slug) {
                    $originalSlug = $newSlug;
                    $count = 1;
                    while (self::findBySlug($newSlug)) {
                        $newSlug = $originalSlug . '-' . $count++;
                    }
                }
            }

            $db->update('articles', [
                'slug' => $newSlug,
                'title' => $title,
                'description' => $description,
                'body' => $body,
                'body_html' => self::compileMarkdown($body),
            ], ['slug' => $slug]);

            if ($tags !== null) {
                $db->delete('article_tags', ['article_id' => $articleId]);

                foreach ($tags as $tagName) {
                    $tagName = trim((string)$tagName);
                    if ($tagName === '') {
                        continue;
                    }

                    $qb = $db->createQueryBuilder();
                    $tagId = $qb->select('id')
                        ->from('tags')
                        ->where('name = :name')
                        ->setParameter('name', $tagName)
                        ->executeQuery()
                        ->fetchOne();

                    if (!$tagId) {
                        $db->insert('tags', ['name' => $tagName]);
                        $tagId = $db->lastInsertId();
                    }

                    try {
                        $db->insert('article_tags', [
                            'article_id' => $articleId,
                            'tag_id' => $tagId
                        ]);
                    } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                        // Already exists
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

    public static function delete(string $slug, int $authorId): bool
    {
        $db = Database::getConnection();
        return $db->delete('articles', ['slug' => $slug, 'author_id' => $authorId]) > 0;
    }

    public static function favorite(int $userId, int $articleId): bool
    {
        $db = Database::getConnection();
        try {
            $db->insert('favorites', [
                'user_id' => $userId,
                'article_id' => $articleId
            ]);
            return true;
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            return true;
        }
    }

    public static function unfavorite(int $userId, int $articleId): bool
    {
        $db = Database::getConnection();
        return $db->delete('favorites', [
            'user_id' => $userId,
            'article_id' => $articleId
        ]) > 0;
    }

    public static function isFavorited(int $userId, int $articleId): bool
    {
        $db = Database::getConnection();
        $qb = $db->createQueryBuilder();

        $result = $qb->select('1')
            ->from('favorites')
            ->where('user_id = :userId')
            ->andWhere('article_id = :articleId')
            ->setParameter('userId', $userId)
            ->setParameter('articleId', $articleId)
            ->executeQuery()
            ->fetchOne();

        return (bool) $result;
    }

    public static function favoritesCount(int $articleId): int
    {
        $db = Database::getConnection();
        $qb = $db->createQueryBuilder();

        return (int) $qb->select('COUNT(*)')
            ->from('favorites')
            ->where('article_id = :articleId')
            ->setParameter('articleId', $articleId)
            ->executeQuery()
            ->fetchOne();
    }

    public static function getGlobalFeed(int $limit = 10, int $offset = 0, ?string $tag = null, ?int $authorId = null): array
    {
        $db = Database::getConnection();
        $qb = $db->createQueryBuilder();

        $qb->select(
            'a.*',
            'u.username as author_username',
            'u.image as author_image',
            self::favoritesCountSubquery() . ' as favoritesCount'
        )
            ->from('articles', 'a')
            ->join('a', 'users', 'u', 'a.author_id = u.id')
            ->orderBy('a.created_at', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($tag) {
            $tagSubquery = $db->createQueryBuilder()
                ->select('at.article_id')
                ->from('article_tags', 'at')
                ->join('at', 'tags', 't', 'at.tag_id = t.id')
                ->where('t.name = :tag')
                ->getSQL();

            $qb->andWhere("a.id IN ({$tagSubquery})")
                ->setParameter('tag', $tag);
        }

        if ($authorId) {
            $qb->andWhere('a.author_id = :authorId')
                ->setParameter('authorId', $authorId);
        }

        $articles = $qb->executeQuery()->fetchAllAssociative();

        return self::attachTags($articles);
    }

    public static function getGlobalFeedCount(?string $tag = null, ?int $authorId = null): int
    {
        $db = Database::getConnection();
        $qb = $db->createQueryBuilder();

        $qb->select('COUNT(*)')
            ->from('articles', 'a');

        if ($tag) {
            $tagSubquery = $db->createQueryBuilder()
                ->select('at.article_id')
                ->from('article_tags', 'at')
                ->join('at', 'tags', 't', 'at.tag_id = t.id')
                ->where('t.name = :tag')
                ->getSQL();

            $qb->andWhere("a.id IN ({$tagSubquery})")
                ->setParameter('tag', $tag);
        }

        if ($authorId) {
            $qb->andWhere('a.author_id = :authorId')
                ->setParameter('authorId', $authorId);
        }

        return (int) $qb->executeQuery()->fetchOne();
    }

    public static function getFeed(int $userId, int $limit = 10, int $offset = 0): array
    {
        $db = Database::getConnection();
        $qb = $db->createQueryBuilder();

        $articles = $qb->select(
            'a.*',
            'u.username as author_username',
            'u.image as author_image',
            self::favoritesCountSubquery() . ' as favoritesCount'
        )
            ->from('articles', 'a')
            ->join('a', 'users', 'u', 'a.author_id = u.id')
            ->join('a', 'follows', 'fo', 'fo.followed_id = a.author_id')
            ->where('fo.follower_id = :userId')
            ->orderBy('a.created_at', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->setParameter('userId', $userId)
            ->executeQuery()
            ->fetchAllAssociative();

        return self::attachTags($articles);
    }

    public static function getFeedCount(int $userId): int
    {
        $db = Database::getConnection();
        $qb = $db->createQueryBuilder();

        return (int) $qb->select('COUNT(*)')
            ->from('articles', 'a')
            ->join('a', 'follows', 'fo', 'fo.followed_id = a.author_id')
            ->where('fo.follower_id = :userId')
            ->setParameter('userId', $userId)
            ->executeQuery()
            ->fetchOne();
    }

    public static function getAllTags(int $minArticleCount = 1): array
    {
        $cacheKey = "tags_{$minArticleCount}";
        if (isset(self::$tagsCache[$cacheKey])) {
            return self::$tagsCache[$cacheKey];
        }

        $db = Database::getConnection();
        $qb = $db->createQueryBuilder();

        $result = $qb->select('t.name')
            ->from('tags', 't')
            ->join('t', 'article_tags', 'at', 't.id = at.tag_id')
            ->groupBy('t.id', 't.name')
            ->having('COUNT(DISTINCT at.article_id) >= :minCount')
            ->orderBy('COUNT(DISTINCT at.article_id)', 'DESC')
            ->addOrderBy('t.name', 'ASC')
            ->setParameter('minCount', $minArticleCount, \Doctrine\DBAL\ParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative();

        $tags = array_column($result, 'name');

        self::$tagsCache[$cacheKey] = $tags;
        return $tags;
    }

    public static function getFavoritedByUser(int $userId, int $limit = 10, int $offset = 0): array
    {
        $db = Database::getConnection();
        $qb = $db->createQueryBuilder();

        // Subquery for favorites count (different alias to avoid conflict with main query's 'f')
        $favCountSub = $db->createQueryBuilder();
        $favCountSubquery = '(' . $favCountSub->select('COUNT(*)')
            ->from('favorites', 'f2')
            ->where('f2.article_id = a.id')
            ->getSQL() . ')';

        $articles = $qb->select(
            'a.*',
            'u.username as author_username',
            'u.image as author_image',
            $favCountSubquery . ' as favoritesCount'
        )
            ->from('favorites', 'f')
            ->join('f', 'articles', 'a', 'f.article_id = a.id')
            ->join('a', 'users', 'u', 'a.author_id = u.id')
            ->where('f.user_id = :userId')
            ->orderBy('a.created_at', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->setParameter('userId', $userId)
            ->executeQuery()
            ->fetchAllAssociative();

        return self::attachTags($articles);
    }

    public static function getFavoritedByUserCount(int $userId): int
    {
        $db = Database::getConnection();
        $qb = $db->createQueryBuilder();

        return (int) $qb->select('COUNT(*)')
            ->from('favorites', 'f')
            ->where('f.user_id = :userId')
            ->setParameter('userId', $userId)
            ->executeQuery()
            ->fetchOne();
    }

    public static function findBySlug(string $slug): ?array
    {
        $db = Database::getConnection();
        $qb = $db->createQueryBuilder();

        $article = $qb->select(
            'a.*',
            'u.username as author_username',
            'u.image as author_image',
            'u.bio as author_bio',
            self::favoritesCountSubquery() . ' as favoritesCount'
        )
            ->from('articles', 'a')
            ->join('a', 'users', 'u', 'a.author_id = u.id')
            ->where('a.slug = :slug')
            ->setParameter('slug', $slug)
            ->executeQuery()
            ->fetchAssociative();

        if (!$article) {
            return null;
        }

        // Fetch tags for single article
        $tagsQb = $db->createQueryBuilder();
        $tags = $tagsQb->select('t.name')
            ->from('tags', 't')
            ->join('t', 'article_tags', 'at', 't.id = at.tag_id')
            ->where('at.article_id = :articleId')
            ->orderBy('t.name')
            ->setParameter('articleId', $article['id'])
            ->executeQuery()
            ->fetchAllAssociative();

        $article['tagList'] = array_column($tags, 'name');

        return $article;
    }

    private static function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = strtolower($text);
        return empty($text) ? 'n-a' : $text;
    }
}
