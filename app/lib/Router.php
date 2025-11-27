<?php

namespace App\Lib;

/**
 * File-based router with [param] support.
 *
 * Maps URLs to files in the pages/ directory:
 *   /                  -> pages/index.php
 *   /login             -> pages/login.php
 *   /article/my-slug   -> pages/article/[slug].php (with $slug = 'my-slug')
 *   /api/articles      -> pages/api/articles.php
 */
class Router
{
    private string $pagesDir;
    private array $params = [];

    public function __construct(string $pagesDir)
    {
        $this->pagesDir = rtrim($pagesDir, '/');
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = '/' . trim($path, '/');

        // Find matching page file
        $resolved = $this->resolve($path);

        if (!$resolved) {
            $this->notFound($path);
            return;
        }

        [$file, $params] = $resolved;

        // CSRF protection for non-GET requests
        if (!in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            Security::requireCsrf();
        }

        // Make params available
        $this->params = $params;

        // Execute the page
        $this->executePage($file, $method, $params);
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getParam(string $name): ?string
    {
        return $this->params[$name] ?? null;
    }

    private function resolve(string $path): ?array
    {
        $segments = $path === '/' ? [] : explode('/', trim($path, '/'));

        // Try exact match first: /login -> pages/login.php
        $exactFile = $this->pagesDir . $path . '.php';
        if ($path !== '/' && file_exists($exactFile)) {
            return [$exactFile, []];
        }

        // Try index file: / -> pages/index.php, /api -> pages/api/index.php
        $indexFile = $this->pagesDir . ($path === '/' ? '' : $path) . '/index.php';
        if (file_exists($indexFile)) {
            return [$indexFile, []];
        }

        // Try parameterized routes
        return $this->resolveWithParams($segments, $this->pagesDir, []);
    }

    private function resolveWithParams(array $segments, string $currentDir, array $params): ?array
    {
        // Base case: no more segments
        if (empty($segments)) {
            $indexFile = $currentDir . '/index.php';
            if (file_exists($indexFile)) {
                return [$indexFile, $params];
            }
            return null;
        }

        $segment = array_shift($segments);
        $remaining = $segments;

        // Try exact match for this segment
        $exactDir = $currentDir . '/' . $segment;
        if (is_dir($exactDir)) {
            $result = $this->resolveWithParams($remaining, $exactDir, $params);
            if ($result) return $result;
        }

        // Try exact file match (if this is the last segment)
        $exactFile = $currentDir . '/' . $segment . '.php';
        if (empty($remaining) && file_exists($exactFile)) {
            return [$exactFile, $params];
        }

        // Try parameterized directory: [slug], [id], etc.
        $dirs = glob($currentDir . '/\[*\]', GLOB_ONLYDIR);
        foreach ($dirs as $paramDir) {
            $paramName = trim(basename($paramDir), '[]');
            $newParams = $params;
            $newParams[$paramName] = $segment;
            $result = $this->resolveWithParams($remaining, $paramDir, $newParams);
            if ($result) return $result;
        }

        // Try parameterized file: [slug].php, [id].php
        $files = glob($currentDir . '/\[*\].php');
        foreach ($files as $paramFile) {
            $paramName = trim(basename($paramFile, '.php'), '[]');
            if (empty($remaining)) {
                $newParams = $params;
                $newParams[$paramName] = $segment;
                return [$paramFile, $newParams];
            }
        }

        return null;
    }

    private function executePage(string $file, string $method, array $params): void
    {
        // Extract params to local scope
        extract($params);

        // Provide helper functions/variables
        $request = (object)[
            'method' => $method,
            'params' => $params,
            'query' => $_GET,
            'body' => $_POST,
            'isHtmx' => isset($_SERVER['HTTP_HX_REQUEST']),
        ];

        ob_start();
        require $file;
        ob_end_flush();
    }

    private function notFound(string $path): void
    {
        http_response_code(404);
        $notFoundFile = $this->pagesDir . '/404.php';
        if (file_exists($notFoundFile)) {
            require $notFoundFile;
        } else {
            echo "404 Not Found: $path";
        }
    }
}
