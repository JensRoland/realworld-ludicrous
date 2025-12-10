<?php

namespace App\Lib;

class View
{
    private static string $templatesDir;
    private static ?object $latte = null;

    public static function init(string $templatesDir): void
    {
        self::$templatesDir = rtrim($templatesDir, '/');
    }

    /**
     * Check if Latte is available.
     */
    public static function hasLatte(): bool
    {
        return class_exists(\Latte\Engine::class);
    }

    /**
     * Get or create the Latte engine instance.
     */
    public static function getLatte(): object
    {
        if (self::$latte === null) {
            if (!self::hasLatte()) {
                throw new \RuntimeException('Latte is not installed. Run: composer require latte/latte');
            }

            self::$latte = new \Latte\Engine();

            // Set cache directory for compiled templates
            $cacheDir = dirname(self::$templatesDir) . '/cache/latte';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            self::$latte->setTempDirectory($cacheDir);

            // Register component extension for auto-discovery
            $componentsDir = dirname(self::$templatesDir) . '/components';
            self::$latte->addExtension(new ComponentExtension($componentsDir));

            // Add useful global functions
            self::$latte->addFunction('thumbnail', [self::class, 'thumbnail']);
            self::$latte->addFunction('csrf_token', fn() => Security::getToken());
        }

        return self::$latte;
    }

    /**
     * Render a template (Latte preferred, PHP fallback).
     */
    public static function render(string $template, array $data = []): void
    {
        // Try Latte template first if Latte is available
        if (self::hasLatte()) {
            $lattePath = self::$templatesDir . '/' . $template . '.latte';
            if (file_exists($lattePath)) {
                self::getLatte()->render($lattePath, $data);
                return;
            }
        }

        // Fall back to PHP template
        $phpPath = self::$templatesDir . '/' . $template . '.php';
        if (file_exists($phpPath)) {
            extract($data);
            require $phpPath;
            return;
        }

        throw new \Exception("Template not found: $template");
    }

    /**
     * Render a template to a string.
     */
    public static function renderToString(string $template, array $data = []): string
    {
        // Try Latte template first if Latte is available
        if (self::hasLatte()) {
            $lattePath = self::$templatesDir . '/' . $template . '.latte';
            if (file_exists($lattePath)) {
                return self::getLatte()->renderToString($lattePath, $data);
            }
        }

        // Fall back to PHP template
        $phpPath = self::$templatesDir . '/' . $template . '.php';
        if (file_exists($phpPath)) {
            extract($data);
            ob_start();
            require $phpPath;
            return ob_get_clean();
        }

        throw new \Exception("Template not found: $template");
    }

    public static function renderLayout(string $contentTemplate, array $data = []): void
    {
        // Pass full path for Latte's {include} tag
        $data['content_template'] = self::$templatesDir . '/' . $contentTemplate;
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
     * Convert an image path to its thumbnail version.
     * Example: /img/avatars/bighead.avif -> /img/avatars/bighead-thumb.avif
     */
    public static function thumbnail(string $path): string
    {
        return preg_replace('/\.([a-z]+)$/i', '-thumb.avif', $path);
    }

    /**
     * Render a component template with props.
     *
     * @param string $template Absolute path to the template file (.latte)
     * @param array $props Variables to pass to the template
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

        if ($return) {
            return self::getLatte()->renderToString($template, $props);
        }
        self::getLatte()->render($template, $props);
        return null;
    }
}
