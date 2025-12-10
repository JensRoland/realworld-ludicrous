<?php

namespace App\Lib;

use Latte\Extension;
use Latte\Runtime\Html;

/**
 * Latte extension that auto-registers all components as template functions.
 *
 * Components are discovered from the components/ directory and registered as:
 * - Top-level: {ArticlePreview($article)} from /components/article-preview/
 * - Nested: {Wiki.Post($article)} from /components/wiki/post/
 */
class ComponentExtension extends Extension
{
    private string $componentsDir;

    public function __construct(string $componentsDir)
    {
        $this->componentsDir = rtrim($componentsDir, '/');
    }

    public function getFunctions(): array
    {
        $functions = [];
        $components = $this->discoverComponents($this->componentsDir);

        foreach ($components as $path => $namespace) {
            $parts = explode('/', $path);

            if (count($parts) === 1) {
                // Top-level component: article-preview → ArticlePreview
                $funcName = $this->toPascalCase($parts[0]);
                $functions[$funcName] = $this->createComponentFunction($namespace);
            } else {
                // Nested component: wiki/post → Wiki.Post
                // Register as a namespace object
                $rootName = $this->toPascalCase($parts[0]);

                if (!isset($functions[$rootName])) {
                    $functions[$rootName] = new ComponentNamespace();
                }

                // Add the nested component to the namespace
                $nestedName = $this->toPascalCase($parts[1]);
                $functions[$rootName]->register($nestedName, $this->createComponentFunction($namespace));
            }
        }

        return $functions;
    }

    /**
     * Discover all components by scanning for controller.php files.
     *
     * @return array<string, string> Map of relative path to PHP namespace
     */
    private function discoverComponents(string $dir, string $prefix = ''): array
    {
        $components = [];

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                $controllerPath = $path . '/controller.php';

                if (file_exists($controllerPath)) {
                    // This is a component directory
                    $relativePath = $prefix ? $prefix . '/' . $item : $item;
                    $namespace = $this->pathToNamespace($relativePath);
                    $components[$relativePath] = $namespace;
                } else {
                    // Check for nested components
                    $nested = $this->discoverComponents($path, $prefix ? $prefix . '/' . $item : $item);
                    $components = array_merge($components, $nested);
                }
            }
        }

        return $components;
    }

    /**
     * Convert a component path to its PHP namespace.
     * e.g., "article-preview" → "App\Components\ArticlePreview"
     * e.g., "wiki/post" → "App\Components\Wiki\Post"
     */
    private function pathToNamespace(string $path): string
    {
        $parts = explode('/', $path);
        $parts = array_map(fn($p) => $this->toPascalCase($p), $parts);
        return 'App\\Components\\' . implode('\\', $parts);
    }

    /**
     * Convert kebab-case to PascalCase.
     * e.g., "article-preview" → "ArticlePreview"
     */
    private function toPascalCase(string $kebab): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $kebab)));
    }

    /**
     * Create a callable that renders a component and returns safe HTML.
     */
    private function createComponentFunction(string $namespace): callable
    {
        $renderFn = $namespace . '\\render';

        return function (...$args) use ($renderFn) {
            ob_start();
            $renderFn(...$args);
            return new Html(ob_get_clean());
        };
    }
}

/**
 * A namespace object that holds nested components.
 * Allows syntax like {Wiki.Post($article)} in Latte templates.
 */
class ComponentNamespace
{
    private array $components = [];

    public function register(string $name, callable $fn): void
    {
        $this->components[$name] = $fn;
    }

    public function __call(string $name, array $args): Html
    {
        if (!isset($this->components[$name])) {
            throw new \RuntimeException("Component '{$name}' not found in namespace");
        }

        return ($this->components[$name])(...$args);
    }
}
