<?php

namespace App\Components\Navbar;

use App\Lib\Auth;
use App\Lib\View;

/**
 * Render the navbar component.
 *
 * @param string|null $currentPage Current page identifier for active state
 */
function render(?string $currentPage = null): void
{
    $currentUser = Auth::user();

    $props = [
        'currentPage' => $currentPage,
        'currentUser' => $currentUser,
        'isLoggedIn' => $currentUser !== null,
    ];

    View::component(__DIR__ . '/template.latte', $props);
}
