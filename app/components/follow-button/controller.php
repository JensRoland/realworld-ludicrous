<?php

namespace App\Components\FollowButton;

use App\Lib\View;

/**
 * Render a follow/unfollow button for a user.
 *
 * @param array $profile User profile with username
 * @param bool $isFollowing Whether the current user is following this profile
 */
function render(array $profile, bool $isFollowing): void
{
    $props = [
        'username' => $profile['username'],
        'isFollowing' => $isFollowing,
        'action' => $isFollowing ? 'unfollow' : 'follow',
        'label' => $isFollowing ? 'Unfollow' : 'Follow',
    ];

    View::component(__DIR__ . '/template.php', $props);
}
