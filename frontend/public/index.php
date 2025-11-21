<?php

require_once __DIR__ . '/../../backend/vendor/autoload.php';

use App\Core\Router;
use App\Core\View;
use App\Core\Security;

$router = new Router();

$authController = new \App\Controllers\AuthController();

$homeController = new \App\Controllers\HomeController();
$router->get('/', [$homeController, 'index']);

$router->get('/login', [$authController, 'loginPage']);
$router->post('/login', [$authController, 'login']);
$router->get('/register', [$authController, 'registerPage']);
$router->post('/register', [$authController, 'register']);
$router->get('/logout', [$authController, 'logout']);
$settingsController = new \App\Controllers\SettingsController();
$router->get('/settings', [$settingsController, 'index']);
$router->post('/settings', [$settingsController, 'update']);

$articleController = new \App\Controllers\ArticleController();

$router->get('/editor/(.+)', [$articleController, 'editPage']);
$router->post('/editor/(.+)', [$articleController, 'update']);
$router->post('/article/(.+)/delete', [$articleController, 'delete']);
$profileController = new \App\Controllers\ProfileController();
$commentController = new \App\Controllers\CommentController();
$router->post('/article/(.+)/comments', [$commentController, 'create']);
$router->delete('/article/(.+)/comment/(.+)', [$commentController, 'delete']);
$router->post('/article/(.+)/favorite', [$articleController, 'favorite']);
$router->post('/article/(.+)/unfavorite', [$articleController, 'unfavorite']);
$router->get('/profile/(.+)', [$profileController, 'index']);
$router->post('/profile/(.+)/follow', [$profileController, 'follow']);
$router->post('/profile/(.+)/unfollow', [$profileController, 'unfollow']);
$router->get('/editor', [$articleController, 'editorPage']);
$router->post('/editor', [$articleController, 'create']);
$router->get('/article/(.+)', [$articleController, 'view']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
