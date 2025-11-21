<?php

namespace App\Core;

class View {
    public static function render(string $template, array $data = []): void {
        extract($data);
        $templatePath = __DIR__ . '/../../templates/' . $template . '.php';

        if (!file_exists($templatePath)) {
            throw new \Exception("Template not found: $templatePath");
        }

        require $templatePath;
    }

    public static function renderLayout(string $contentTemplate, array $data = []): void {
        $data['content_template'] = $contentTemplate;
        $data['currentPage'] = self::getCurrentPage();
        self::render('layout', $data);
    }

    private static function getCurrentPage(): string {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        if ($path === '/') {
            return 'home';
        } elseif ($path === '/editor') {
            return 'editor';
        } elseif ($path === '/settings') {
            return 'settings';
        } elseif ($path === '/login') {
            return 'login';
        } elseif ($path === '/register') {
            return 'register';
        } elseif (str_starts_with($path, '/profile/')) {
            return 'profile';
        } elseif (str_starts_with($path, '/article/')) {
            return 'article';
        }

        return '';
    }
}
