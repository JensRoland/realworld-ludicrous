<?php

use App\Lib\View;

$path = $_SERVER['REQUEST_URI'] ?? '';

View::renderLayout('404', ['path' => $path]);
