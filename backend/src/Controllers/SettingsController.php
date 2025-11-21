<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\Auth;
use App\Models\User;

class SettingsController {
    public function index(): void {
        Auth::require();

        $user = User::findById(Auth::userId());
        View::renderLayout('settings', ['user' => $user]);
    }

    public function update(): void {
        Auth::require();

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
                // Regenerate JWT token with updated info
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
}