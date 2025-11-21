<?php

namespace App\Core;

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
            // In production, this should come from environment variable
            self::$secret = getenv('JWT_SECRET') ?: 'your-secret-key-change-this-in-production';
        }
        return self::$secret;
    }

    /**
     * Generate a JWT token for a user
     */
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

    /**
     * Validate and decode a JWT token
     */
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

    /**
     * Get the current authenticated user from the request
     */
    public static function user(): ?array
    {
        $token = self::getTokenFromRequest();
        $payload = self::validateToken($token);

        if (!$payload) {
            return null;
        }

        // Fetch full user data from database to get image, bio, etc.
        $userId = $payload['sub'] ?? null;
        if ($userId) {
            $user = User::findById($userId);
            return $user;
        }

        return null;
    }

    /**
     * Get the current user ID
     */
    public static function userId(): ?int
    {
        $user = self::user();
        return $user['id'] ?? null;
    }

    /**
     * Check if a user is authenticated
     */
    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * Require authentication or redirect to login
     */
    public static function require(): void
    {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Extract JWT token from request headers or cookies
     */
    private static function getTokenFromRequest(): ?string
    {
        // Try Authorization header first
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }

        // Fallback to cookie
        return $_COOKIE['jwt_token'] ?? null;
    }

    /**
     * Set JWT token as an HTTP-only cookie
     */
    public static function setTokenCookie(string $token): void
    {
        setcookie(
            'jwt_token',
            $token,
            [
                'expires' => time() + self::$expiration,
                'path' => '/',
                'httponly' => true,
                'secure' => false, // Set to true in production with HTTPS
                'samesite' => 'Lax'
            ]
        );
    }

    /**
     * Clear JWT token cookie
     */
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
