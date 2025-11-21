<?php

namespace App\Services;

use App\Core\Database;
use App\Models\User;
use App\Models\Article;
use App\Models\Comment;
use Doctrine\DBAL\Connection;
use Symfony\Component\Yaml\Yaml;

class Seeder
{
    public static function seed(?string $dataFile = null): array
    {
        $db = Database::getConnection();
        self::ensureSchema($db);

        $summary = [
            'users_before' => (int) self::count($db, 'users'),
            'articles_before' => (int) self::count($db, 'articles'),
            'tags_before' => (int) self::count($db, 'tags'),
            'comments_before' => (int) self::count($db, 'comments'),
            'favorites_before' => (int) self::count($db, 'favorites'),
            'follows_before' => (int) self::count($db, 'follows'),
        ];

        // Load seed data from YAML file
        if ($dataFile === null) {
            $dataFile = __DIR__ . '/../../data/seed.yaml';
        }

        if (!is_readable($dataFile)) {
            throw new \RuntimeException("Cannot read seed data file: {$dataFile}");
        }

        $data = Yaml::parseFile($dataFile);

        // Map to store user objects by username
        $users = [];

        // Seed users
        foreach ($data['users'] ?? [] as $userData) {
            $user = self::upsertUser(
                $userData['username'],
                $userData['email'],
                $userData['password'],
                $userData['bio'] ?? null,
                $userData['image'] ?? null
            );
            if ($user) {
                $users[$userData['username']] = $user;
            }
        }

        // Seed follows
        foreach ($data['follows'] ?? [] as $followData) {
            $follower = $users[$followData['follower']] ?? null;
            $followee = $users[$followData['followee']] ?? null;
            if ($follower && $followee) {
                User::follow($follower['id'], $followee['id']);
            }
        }

        // Map to store articles by title
        $articles = [];

        // Seed articles (check each one individually for idempotency)
        foreach ($data['articles'] ?? [] as $articleData) {
            $author = $users[$articleData['author']] ?? null;
            if ($author) {
                // Check if article already exists by title
                $existingArticle = $db->fetchAssociative('SELECT * FROM articles WHERE title = ?', [$articleData['title']]);

                if (!$existingArticle) {
                    // Article doesn't exist, create it
                    $slug = Article::create(
                        $articleData['title'],
                        $articleData['description'],
                        $articleData['body'],
                        $articleData['tags'] ?? [],
                        $author['id']
                    );
                    if ($slug) {
                        $article = Article::findBySlug($slug);
                        if ($article) {
                            $articles[$articleData['title']] = $article;
                        }
                    }
                } else {
                    // Article exists, add to map for favorites/comments
                    $articles[$articleData['title']] = $existingArticle;
                }
            }
        }

        // Seed favorites
        $skippedFavorites = [];
        foreach ($data['favorites'] ?? [] as $favoriteData) {
            $user = $users[$favoriteData['user']] ?? null;
            $article = $articles[$favoriteData['article']] ?? null;
            if (!$user) {
                $skippedFavorites[] = "User not found: {$favoriteData['user']}";
                continue;
            }
            if (!$article) {
                $skippedFavorites[] = "Article not found: {$favoriteData['article']}";
                continue;
            }
            // Check if favorite already exists
            $exists = $db->fetchOne('SELECT 1 FROM favorites WHERE user_id = ? AND article_id = ?', [$user['id'], $article['id']]);
            if (!$exists) {
                Article::favorite($user['id'], $article['id']);
            }
        }

        // Seed comments
        $skippedComments = [];
        foreach ($data['comments'] ?? [] as $commentData) {
            $author = $users[$commentData['author']] ?? null;
            $article = $articles[$commentData['article']] ?? null;
            if (!$author) {
                $skippedComments[] = "Author not found: {$commentData['author']} (article: {$commentData['article']})";
                continue;
            }
            if (!$article) {
                $skippedComments[] = "Article not found: {$commentData['article']} (author: {$commentData['author']})";
                continue;
            }
            // Check if comment already exists (by body and author)
            $exists = $db->fetchOne('SELECT 1 FROM comments WHERE article_id = ? AND author_id = ? AND body = ?', [$article['id'], $author['id'], $commentData['body']]);
            if (!$exists) {
                Comment::create($commentData['body'], $article['id'], $author['id']);
            }
        }

        // Output skipped items
        if (!empty($skippedComments)) {
            echo "\nSkipped Comments:\n";
            foreach ($skippedComments as $skip) {
                echo "  - $skip\n";
            }
        }
        if (!empty($skippedFavorites)) {
            echo "\nSkipped Favorites:\n";
            foreach ($skippedFavorites as $skip) {
                echo "  - $skip\n";
            }
        }

        $summary += [
            'users_after' => (int) self::count($db, 'users'),
            'articles_after' => (int) self::count($db, 'articles'),
            'tags_after' => (int) self::count($db, 'tags'),
            'comments_after' => (int) self::count($db, 'comments'),
            'favorites_after' => (int) self::count($db, 'favorites'),
            'follows_after' => (int) self::count($db, 'follows'),
        ];

        // Calculate added counts
        $summary['users_added'] = $summary['users_after'] - $summary['users_before'];
        $summary['articles_added'] = $summary['articles_after'] - $summary['articles_before'];
        $summary['tags_added'] = $summary['tags_after'] - $summary['tags_before'];
        $summary['comments_added'] = $summary['comments_after'] - $summary['comments_before'];
        $summary['favorites_added'] = $summary['favorites_after'] - $summary['favorites_before'];
        $summary['follows_added'] = $summary['follows_after'] - $summary['follows_before'];

        return $summary;
    }

    private static function ensureSchema(Connection $db): void
    {
        $schemaPath = __DIR__ . '/../../schema.sql';
        if (is_readable($schemaPath)) {
            $sql = file_get_contents($schemaPath);
            if ($sql !== false) {
                // Split statements by semicolon and execute each one
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $db->executeStatement($statement);
                    }
                }
            }
        }
    }

    private static function count(Connection $db, string $table): int
    {
        return (int) $db->fetchOne("SELECT COUNT(*) FROM {$table}");
    }

    private static function upsertUser(string $username, string $email, string $password, ?string $bio, ?string $image): ?array
    {
        $existing = User::findByEmail($email);
        if (!$existing) {
            User::create($username, $email, $password);
            $existing = User::findByEmail($email);
            if ($existing) {
                // Update optional fields if provided
                User::update($existing['id'], $username, $email, null, $image, $bio);
                $existing = User::findByEmail($email);
            }
        }
        return $existing ?: null;
    }
}