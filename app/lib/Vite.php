<?php

declare(strict_types=1);

namespace App\Lib;

class Vite
{
    private static ?array $manifest = null;
    private static string $manifestPath = __DIR__ . '/../public/dist/.vite/manifest.json';
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

        // CSS files
        if (isset($entryData['css'])) {
            foreach ($entryData['css'] as $cssFile) {
                $html .= '<link rel="stylesheet" href="/dist/' . $cssFile . '">' . "\n";
            }
        }

        // JS file
        if (isset($entryData['file'])) {
            $html .= '<script type="module" src="/dist/' . $entryData['file'] . '"></script>';
        }

        return $html;
    }
}
