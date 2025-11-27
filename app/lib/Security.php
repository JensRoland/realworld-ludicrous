<?php

namespace App\Lib;

class Security
{
    public static function getToken(): string
    {
        $userId = Auth::userId();
        if ($userId) {
            $secret = getenv('JWT_SECRET') ?: 'your-secret-key-change-this-in-production';
            return hash_hmac('sha256', (string)$userId, $secret);
        }

        return hash('sha256', 'anonymous-user');
    }

    public static function validate(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $expectedToken = self::getToken();
        return hash_equals($expectedToken, $token);
    }

    public static function requireCsrf(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return;
        }

        $token = null;

        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        } else {
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                if (isset($headers['X-CSRF-Token'])) {
                    $token = $headers['X-CSRF-Token'];
                }
            }
        }

        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
        }

        if (!self::validate($token)) {
            http_response_code(419);
            echo 'CSRF token invalid';
            exit;
        }
    }
}
