<?php

namespace App\Components\ErrorMessages;

use App\Lib\View;

/**
 * Render error messages component.
 *
 * @param string|array|null $errors Error message(s) to display
 */
function render(string|array|null $errors): void
{
    if (!empty($errors)) {
        View::component(__DIR__ . '/template.latte', ['errors' => (array) $errors]);
    }
}
