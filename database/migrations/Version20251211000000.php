<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial schema migration - creates all tables for the RealWorld app.
 *
 * This migration represents the baseline schema. For existing databases,
 * mark it as executed without running: php migrations.php migrations:version --add Version20251211000000
 */
final class Version20251211000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema: users, articles, tags, comments, favorites, follows';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        // Users table
        $users = $schema->createTable('users');
        $users->addColumn('id', 'integer', ['autoincrement' => true]);
        $users->addColumn('username', 'string', ['length' => 255]);
        $users->addColumn('email', 'string', ['length' => 255]);
        $users->addColumn('password_hash', 'string', ['length' => 255]);
        $users->addColumn('bio', 'text', ['notnull' => false]);
        $users->addColumn('image', 'string', ['length' => 512, 'notnull' => false]);
        $users->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $users->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $users->setPrimaryKey(['id']);
        $users->addUniqueIndex(['username'], 'users_username_unique');
        $users->addUniqueIndex(['email'], 'users_email_unique');

        // Articles table
        $articles = $schema->createTable('articles');
        $articles->addColumn('id', 'integer', ['autoincrement' => true]);
        $articles->addColumn('slug', 'string', ['length' => 255]);
        $articles->addColumn('title', 'string', ['length' => 255]);
        $articles->addColumn('description', 'text');
        $articles->addColumn('body', 'text');
        $articles->addColumn('body_html', 'text', ['notnull' => false]);
        $articles->addColumn('author_id', 'integer');
        $articles->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $articles->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $articles->setPrimaryKey(['id']);
        $articles->addUniqueIndex(['slug'], 'articles_slug_unique');
        $articles->addIndex(['author_id'], 'articles_author_id_idx');
        $articles->addForeignKeyConstraint('users', ['author_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_articles_author');

        // Tags table
        $tags = $schema->createTable('tags');
        $tags->addColumn('id', 'integer', ['autoincrement' => true]);
        $tags->addColumn('name', 'string', ['length' => 255]);
        $tags->setPrimaryKey(['id']);
        $tags->addUniqueIndex(['name'], 'tags_name_unique');

        // Article-Tags pivot table
        $articleTags = $schema->createTable('article_tags');
        $articleTags->addColumn('article_id', 'integer');
        $articleTags->addColumn('tag_id', 'integer');
        $articleTags->setPrimaryKey(['article_id', 'tag_id']);
        $articleTags->addForeignKeyConstraint('articles', ['article_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_article_tags_article');
        $articleTags->addForeignKeyConstraint('tags', ['tag_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_article_tags_tag');

        // Comments table
        $comments = $schema->createTable('comments');
        $comments->addColumn('id', 'integer', ['autoincrement' => true]);
        $comments->addColumn('body', 'text');
        $comments->addColumn('article_id', 'integer');
        $comments->addColumn('author_id', 'integer');
        $comments->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $comments->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $comments->setPrimaryKey(['id']);
        $comments->addIndex(['article_id'], 'comments_article_id_idx');
        $comments->addIndex(['author_id'], 'comments_author_id_idx');
        $comments->addForeignKeyConstraint('articles', ['article_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_comments_article');
        $comments->addForeignKeyConstraint('users', ['author_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_comments_author');

        // Favorites pivot table
        $favorites = $schema->createTable('favorites');
        $favorites->addColumn('user_id', 'integer');
        $favorites->addColumn('article_id', 'integer');
        $favorites->setPrimaryKey(['user_id', 'article_id']);
        $favorites->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_favorites_user');
        $favorites->addForeignKeyConstraint('articles', ['article_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_favorites_article');

        // Follows pivot table
        $follows = $schema->createTable('follows');
        $follows->addColumn('follower_id', 'integer');
        $follows->addColumn('followed_id', 'integer');
        $follows->setPrimaryKey(['follower_id', 'followed_id']);
        $follows->addForeignKeyConstraint('users', ['follower_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_follows_follower');
        $follows->addForeignKeyConstraint('users', ['followed_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_follows_followed');
    }

    public function down(Schema $schema): void
    {
        // Drop in reverse dependency order
        $schema->dropTable('follows');
        $schema->dropTable('favorites');
        $schema->dropTable('comments');
        $schema->dropTable('article_tags');
        $schema->dropTable('tags');
        $schema->dropTable('articles');
        $schema->dropTable('users');
    }
}
