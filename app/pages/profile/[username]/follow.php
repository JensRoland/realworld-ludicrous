<?php

use App\Lib\Auth;
use App\Models\User;

if (!Auth::check()) {
    header('Location: /login');
    exit;
}

$profile = User::findByUsername($username);
if ($profile) {
    if ($profile['id'] === Auth::userId()) {
        http_response_code(400);
        echo "Cannot follow yourself";
        return;
    }
    User::follow(Auth::userId(), $profile['id']);
}

\App\Components\FollowButton\render($profile, true);
