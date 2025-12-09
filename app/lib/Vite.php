<?php

declare(strict_types=1);

namespace App\Lib;

class Vite
{
    private static ?array $manifest = null;
    private static string $manifestPath = __DIR__ . '/../public/dist/.vite/manifest.json';
    private static string $criticalCssPath = __DIR__ . '/../public/dist/assets/critical.css';
    private static string $devServerUrl = 'http://localhost:5173';

    public static function isDev(): bool
    {
        static $isDev = null;
        if ($isDev === null) {
            $isDev = @fsockopen('localhost', 5173, $errno, $errstr, 0.1) !== false;
        }
        return $isDev;
    }

    private static function getManifest(): array
    {
        if (self::$manifest === null) {
            $path = self::$manifestPath;
            if (!file_exists($path)) {
                throw new \RuntimeException("Vite manifest not found. Run 'npm run build' first.");
            }
            self::$manifest = json_decode(file_get_contents($path), true);
        }
        return self::$manifest;
    }

    public static function assets(string $entry = 'resources/js/app.js'): string
    {
        if (self::isDev()) {
            return self::devAssets($entry);
        }
        return self::prodAssets($entry);
    }

    private static function devAssets(string $entry): string
    {
        $url = self::$devServerUrl;
        return <<<HTML
            <script type="module" src="{$url}/@vite/client"></script>
            <script type="module" src="{$url}/{$entry}"></script>
            HTML;
    }

    private static function prodAssets(string $entry): string
    {
        $manifest = self::getManifest();

        if (!isset($manifest[$entry])) {
            throw new \RuntimeException("Entry '{$entry}' not found in Vite manifest.");
        }

        $entryData = $manifest[$entry];
        $html = '';

        // Inline critical CSS for fast first paint
        if (file_exists(self::$criticalCssPath)) {
            $criticalCss = file_get_contents(self::$criticalCssPath);
            $html .= '<style>' . $criticalCss . '</style>' . "\n";
        }

        // Load full CSS asynchronously (non-render-blocking)
        if (isset($entryData['css'])) {
            foreach ($entryData['css'] as $cssFile) {
                $href = '/dist/' . $cssFile;
                // Preload + swap pattern for async CSS loading
                $html .= '<link rel="preload" href="' . $href . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n";
                $html .= '<noscript><link rel="stylesheet" href="' . $href . '"></noscript>' . "\n";
            }
        }

        // JS file (preload + execute)
        if (isset($entryData['file'])) {
            $jsHref = '/dist/' . $entryData['file'];
            $html .= '<link rel="modulepreload" href="' . $jsHref . '">' . "\n";
            $html .= '<script type="module" src="' . $jsHref . '"></script>';
        }

        return $html;
    }
}
