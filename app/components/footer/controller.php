<?php

namespace App\Components\Footer;

use App\Lib\View;

/**
 * Render the footer component.
 */
function render(): void
{
    View::component(__DIR__ . '/template.latte', []);
}
