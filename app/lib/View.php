<?php

namespace App\Lib;

class View
{
    private static string $templatesDir;

    public static function init(string $templatesDir): void
    {
        self::$templatesDir = rtrim($templatesDir, '/');
    }

    public static function render(string $template, array $data = []): void
    {
        extract($data);
        $templatePath = self::$templatesDir . '/' . $template . '.php';

        if (!file_exists($templatePath)) {
            throw new \Exception("Template not found: $templatePath");
        }

        require $templatePath;
    }

    public static function renderLayout(string $contentTemplate, array $data = []): void
    {
        $data['content_template'] = $contentTemplate;
        $data['currentPage'] = self::getCurrentPage();
        self::render('layout', $data);
    }

    private static function getCurrentPage(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        return match (true) {
            $path === '/' => 'home',
            $path === '/editor' => 'editor',
            $path === '/settings' => 'settings',
            $path === '/login' => 'login',
            $path === '/register' => 'register',
            str_starts_with($path, '/profile/') => 'profile',
            str_starts_with($path, '/article/') => 'article',
            default => ''
        };
    }

    /**
     * Render a component template with props.
     *
     * @param string $template Absolute path to the template file
     * @param array $props Variables to extract into template scope
     * @param bool $return Return the output instead of echoing
     * @param string|null $format Output format: null (HTML), 'json'
     * @return string|null The rendered output if $return is true, null otherwise
     */
    public static function component(string $template, array $props, bool $return = false, ?string $format = null): ?string
    {
        if ($format === 'json') {
            $json = json_encode($props, JSON_THROW_ON_ERROR);
            if ($return) {
                return $json;
            }
            header('Content-Type: application/json');
            echo $json;
            return null;
        }

        extract($props);
        if ($return) {
            ob_start();
            include $template;
            return ob_get_clean();
        }
        include $template;
        return null;
    }
}
