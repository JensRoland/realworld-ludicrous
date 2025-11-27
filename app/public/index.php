<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Lib\Router;
use App\Lib\View;

// Initialize View with templates directory
View::init(__DIR__ . '/../templates');

// Create router with pages directory
$router = new Router(__DIR__ . '/../pages');

// Dispatch the request
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
