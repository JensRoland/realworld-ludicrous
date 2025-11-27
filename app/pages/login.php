<?php

use App\Lib\Auth;
use App\Lib\View;
use App\Models\User;

match ($request->method) {
    'GET' => showLoginPage(),
    'POST' => handleLogin(),
    default => abort(405),
};

function showLoginPage(): void
{
    View::renderLayout('login');
}

function handleLogin(): void
{
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $user = User::findByEmail($email);

    if ($user && password_verify($password, $user['password_hash'])) {
        $token = Auth::generateToken($user['id'], $user['username'], $user['email']);
        Auth::setTokenCookie($token);

        header('Location: /');
        exit;
    }

    View::renderLayout('login', ['error' => 'Invalid email or password']);
}

function abort(int $code): void
{
    http_response_code($code);
    echo "Method not allowed";
}
