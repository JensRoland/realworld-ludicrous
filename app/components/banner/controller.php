<?php

namespace App\Components\Banner;

use App\Lib\View;

/**
 * Render the homepage banner/hero component.
 */
function render(): void
{
    View::component(__DIR__ . '/template.latte', []);
}
