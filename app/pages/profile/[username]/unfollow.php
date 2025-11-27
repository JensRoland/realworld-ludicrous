<?php

use App\Lib\Auth;
use App\Models\User;

if (!Auth::check()) {
    header('HX-Redirect: /login');
    exit;
}

$profile = User::findByUsername($username);
if ($profile) {
    if ($profile['id'] === Auth::userId()) {
        http_response_code(400);
        echo "Cannot unfollow yourself";
        return;
    }
    User::unfollow(Auth::userId(), $profile['id']);
}

\App\Components\FollowButton\render($profile, false);
