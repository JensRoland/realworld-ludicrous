<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\Auth;
use App\Models\User;

class AuthController {
    public function loginPage(): void {
        View::renderLayout('login');
    }

    public function registerPage(): void {
        View::renderLayout('register');
    }

    public function register(): void {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Basic validation
        if (empty($username) || empty($email) || empty($password)) {
            View::renderLayout('register', ['error' => 'All fields are required']);
            return;
        }

        try {
            $userId = User::create($username, $email, $password);
            if ($userId) {
                // Generate JWT token and set cookie
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

    public function login(): void {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = User::findByEmail($email);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Generate JWT token and set cookie
            $token = Auth::generateToken($user['id'], $user['username'], $user['email']);
            Auth::setTokenCookie($token);

            header('Location: /');
            exit;
        }

        View::renderLayout('login', ['error' => 'Invalid email or password']);
    }

    public function logout(): void {
        Auth::clearTokenCookie();
        header('Location: /');
        exit;
    }
}
