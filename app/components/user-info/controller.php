<?php

namespace App\Components\UserInfo;

use App\Lib\Auth;
use App\Lib\View;

/**
 * Render the user profile header component.
 *
 * @param array $profile User profile data
 * @param bool $isFollowing Whether current user is following this profile
 */
function render(array $profile, bool $isFollowing = false): void
{
    $currentUserId = Auth::userId();
    $isOwnProfile = $currentUserId && $currentUserId == $profile['id'];
    $isLoggedIn = Auth::check();

    $props = [
        'profile' => $profile,
        'isOwnProfile' => $isOwnProfile,
        'isLoggedIn' => $isLoggedIn,
        'isFollowing' => $isFollowing,
    ];

    View::component(__DIR__ . '/template.latte', $props);
}
