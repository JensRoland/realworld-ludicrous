<?php

namespace App\Core;

class Security
{
    /**
     * Generate a CSRF token
     * With JWT auth, we derive CSRF token from the user ID to keep it stable
     */
    public static function getToken(): string
    {
        // For authenticated users, create a stable token based on user ID
        $userId = Auth::userId();
        if ($userId) {
            // Use a secret key for HMAC - in production this should come from env
            $secret = getenv('JWT_SECRET') ?: 'your-secret-key-change-this-in-production';
            return hash_hmac('sha256', (string)$userId, $secret);
        }

        // For non-authenticated users, return a static token
        // This is less critical since they can't perform most state-changing operations
        return hash('sha256', 'anonymous-user');
    }

    /**
     * Validate CSRF token
     * With JWT-based auth, CSRF protection works by:
     * 1. Verifying the token matches the authenticated user
     * 2. JWTs in httpOnly cookies prevent CSRF by themselves
     * 3. This adds an extra layer of protection
     */
    public static function validate(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $expectedToken = self::getToken();
        return hash_equals($expectedToken, $token);
    }

    /**
     * Require CSRF validation for state-changing requests
     */
    public static function requireCsrf(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'GET' || $method === 'HEAD' || $method === 'OPTIONS') {
            return;
        }

        $token = null;

        // Prefer header (HTMX/AJAX)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        } else {
            // Fallback to typical header name via getallheaders()
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                if (isset($headers['X-CSRF-Token'])) {
                    $token = $headers['X-CSRF-Token'];
                }
            }
        }

        // Fallback to form/query
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
        }

        if (!self::validate($token)) {
            http_response_code(419); // Authentication Timeout (used by many frameworks for CSRF)
            echo 'CSRF token invalid';
            exit;
        }
    }
}