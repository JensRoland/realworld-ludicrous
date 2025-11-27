<?php

use App\Lib\Auth;
use App\Lib\View;
use App\Models\User;

match ($request->method) {
    'GET' => showRegisterPage(),
    'POST' => handleRegister(),
    default => abort(405),
};

function showRegisterPage(): void
{
    View::renderLayout('register');
}

function handleRegister(): void
{
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        View::renderLayout('register', ['error' => 'All fields are required']);
        return;
    }

    try {
        $userId = User::create($username, $email, $password);
        if ($userId) {
            $token = Auth::generateToken($userId, $username, $email);
            Auth::setTokenCookie($token);

            header('Location: /');
            exit;
        }
    } catch (\Exception $e) {
        View::renderLayout('register', ['error' => 'Registration failed. Username or email might be taken.']);
        return;
    }
}

function abort(int $code): void
{
    http_response_code($code);
    echo "Method not allowed";
}
