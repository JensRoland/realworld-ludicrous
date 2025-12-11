<?php

namespace App\Models;

use App\Lib\Database;

class User
{
    public static function create(string $username, string $email, string $password): int
    {
        $db = Database::getConnection();
        $db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT)
        ]);
        return (int) $db->lastInsertId();
    }

    public static function findByEmail(string $email): ?array
    {
        $db = Database::getConnection();
        $qb = $db->createQueryBuilder();

        $result = $qb->select('*')
            ->from('users')
            ->where('email = :email')
            ->setParameter('email', $email)
            ->executeQuery()
            ->fetchAssociative();

        return $result ?: null;
    }

    public static function findByUsername(string $username): ?array
    {
        $db = Database::getConnection();
        $qb = $db->createQueryBuilder();

        $result = $qb->select('*')
            ->from('users')
            ->where('username = :username')
            ->setParameter('username', $username)
            ->executeQuery()
            ->fetchAssociative();

        return $result ?: null;
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $qb = $db->createQueryBuilder();

        $result = $qb->select('*')
            ->from('users')
            ->where('id = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchAssociative();

        return $result ?: null;
    }

    public static function update(int $id, string $username, string $email, ?string $password, ?string $image, ?string $bio): bool
    {
        $db = Database::getConnection();

        $data = [
            'username' => $username,
            'email' => $email,
            'image' => $image,
            'bio' => $bio
        ];

        if ($password) {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        return $db->update('users', $data, ['id' => $id]) > 0;
    }

    public static function follow(int $followerId, int $followedId): bool
    {
        $db = Database::getConnection();
        try {
            $db->insert('follows', [
                'follower_id' => $followerId,
                'followed_id' => $followedId
            ]);
            return true;
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            return true;
        }
    }

    public static function unfollow(int $followerId, int $followedId): bool
    {
        $db = Database::getConnection();
        return $db->delete('follows', [
            'follower_id' => $followerId,
            'followed_id' => $followedId
        ]) > 0;
    }

    public static function isFollowing(int $followerId, int $followedId): bool
    {
        $db = Database::getConnection();
        $qb = $db->createQueryBuilder();

        $result = $qb->select('1')
            ->from('follows')
            ->where('follower_id = :followerId')
            ->andWhere('followed_id = :followedId')
            ->setParameter('followerId', $followerId)
            ->setParameter('followedId', $followedId)
            ->executeQuery()
            ->fetchOne();

        return (bool) $result;
    }
}
