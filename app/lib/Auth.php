<?php

namespace App\Lib;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;

class Auth
{
    private static string $secret;
    private static string $algorithm = 'HS256';
    private static int $expiration = 86400 * 7; // 7 days

    private static function getSecret(): string
    {
        if (!isset(self::$secret)) {
            self::$secret = getenv('JWT_SECRET') ?: 'your-secret-key-change-this-in-production';
        }
        return self::$secret;
    }

    public static function generateToken(int $userId, string $username, string $email): string
    {
        $issuedAt = time();
        $expire = $issuedAt + self::$expiration;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'sub' => $userId,
            'username' => $username,
            'email' => $email
        ];

        return JWT::encode($payload, self::getSecret(), self::$algorithm);
    }

    public static function validateToken(?string $token): ?array
    {
        if (!$token) {
            return null;
        }

        try {
            $decoded = JWT::decode($token, new Key(self::getSecret(), self::$algorithm));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function user(): ?array
    {
        $token = self::getTokenFromRequest();
        $payload = self::validateToken($token);

        if (!$payload) {
            return null;
        }

        $userId = $payload['sub'] ?? null;
        if ($userId) {
            return User::findById($userId);
        }

        return null;
    }

    public static function userId(): ?int
    {
        $user = self::user();
        return $user['id'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function require(): void
    {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
    }

    private static function getTokenFromRequest(): ?string
    {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }

        return $_COOKIE['jwt_token'] ?? null;
    }

    public static function setTokenCookie(string $token): void
    {
        setcookie(
            'jwt_token',
            $token,
            [
                'expires' => time() + self::$expiration,
                'path' => '/',
                'httponly' => true,
                'secure' => false,
                'samesite' => 'Lax'
            ]
        );
    }

    public static function clearTokenCookie(): void
    {
        setcookie(
            'jwt_token',
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true,
                'secure' => false,
                'samesite' => 'Lax'
            ]
        );
    }
}
