<?php

namespace App\Components\ProfileTabs;

use App\Lib\View;

/**
 * Render profile article tabs (My Articles / Favorited Articles).
 *
 * @param string $username Profile username for URLs
 * @param string $activeTab Currently active tab ('my' or 'favorites')
 */
function render(string $username, string $activeTab = 'my'): void
{
    $props = [
        'username' => $username,
        'activeTab' => $activeTab,
    ];

    View::component(__DIR__ . '/template.latte', $props);
}
