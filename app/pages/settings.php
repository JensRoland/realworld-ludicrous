<?php

use App\Lib\Auth;
use App\Lib\View;
use App\Models\User;

Auth::require();

match ($request->method) {
    'GET' => showSettings(),
    'POST' => updateSettings(),
    default => abort(405),
};

function showSettings(): void
{
    $user = User::findById(Auth::userId());
    View::renderLayout('settings', ['user' => $user]);
}

function updateSettings(): void
{
    $userId = Auth::userId();
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = !empty($_POST['password']) ? $_POST['password'] : null;
    $image = $_POST['image'] ?? null;
    $bio = $_POST['bio'] ?? null;

    if (empty($username) || empty($email)) {
        $user = User::findById($userId);
        View::renderLayout('settings', [
            'user' => $user,
            'error' => 'Username and email are required'
        ]);
        return;
    }

    try {
        if (User::update($userId, $username, $email, $password, $image, $bio)) {
            $token = Auth::generateToken($userId, $username, $email);
            Auth::setTokenCookie($token);

            header('Location: /profile/' . $username);
            exit;
        }
    } catch (\Exception $e) {
        $user = User::findById($userId);
        View::renderLayout('settings', [
            'user' => $user,
            'error' => 'Update failed. Username or email might be taken.'
        ]);
        return;
    }
}

function abort(int $code): void
{
    http_response_code($code);
    echo "Method not allowed";
}
