<?php

namespace App\Components\FeedToggle;

use App\Lib\Auth;
use App\Lib\View;

/**
 * Render the feed toggle navigation component.
 *
 * @param string $activeFeed Currently active feed ('your' or 'global')
 * @param string|null $activeTag Currently selected tag, if any
 */
function render(string $activeFeed, ?string $activeTag = null): void
{
    $props = [
        'activeFeed' => $activeFeed,
        'activeTag' => $activeTag,
        'isLoggedIn' => Auth::check(),
    ];

    View::component(__DIR__ . '/template.latte', $props);
}
