<?php

namespace App\Components\CommentForm;

use App\Lib\Auth;
use App\Lib\Security;
use App\Lib\View;
use App\Models\User;

/**
 * Render the comment form component.
 *
 * @param string $articleSlug Article slug for form action
 */
function render(string $articleSlug): void
{
    $currentUser = Auth::user();

    if (!$currentUser) {
        View::component(__DIR__ . '/template-guest.latte', []);
        return;
    }

    // Get full user data for avatar
    $userData = User::findById($currentUser['id']);

    $props = [
        'articleSlug' => $articleSlug,
        'userImage' => $userData['image'] ?? '/img/smiley-cyrus.avif',
        'csrfToken' => Security::getToken(),
    ];

    View::component(__DIR__ . '/template.latte', $props);
}
