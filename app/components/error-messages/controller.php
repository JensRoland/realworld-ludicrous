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
    if ($errors === null) {
        return;
    }

    // Normalize to array
    $errorList = is_array($errors) ? $errors : [$errors];

    if (empty($errorList)) {
        return;
    }

    View::component(__DIR__ . '/template.latte', ['errors' => $errorList]);
}
