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
 *
 * Components can be either:
 * - Full components with controller.php (calls namespace\render())
 * - Template-only components with just template.latte (renders directly)
 */
class ComponentExtension extends Extension
{
    private string $componentsDir;
    private ?object $latte = null;

    public function __construct(string $componentsDir)
    {
        $this->componentsDir = rtrim($componentsDir, '/');
    }

    /**
     * Set the Latte engine instance for rendering template-only components.
     */
    public function setLatte(object $latte): void
    {
        $this->latte = $latte;
    }

    public function getFunctions(): array
    {
        $functions = [];
        $components = $this->discoverComponents($this->componentsDir);

        foreach ($components as $path => $info) {
            $parts = explode('/', $path);

            if (count($parts) === 1) {
                // Top-level component: article-preview → ArticlePreview
                $funcName = $this->toPascalCase($parts[0]);
                $functions[$funcName] = $this->createComponentFunction($info);
            } else {
                // Nested component: wiki/post → Wiki.Post
                // Register as a namespace object
                $rootName = $this->toPascalCase($parts[0]);

                if (!isset($functions[$rootName])) {
                    $functions[$rootName] = new ComponentNamespace();
                }

                // Add the nested component to the namespace
                $nestedName = $this->toPascalCase($parts[1]);
                $functions[$rootName]->register($nestedName, $this->createComponentFunction($info));
            }
        }

        return $functions;
    }

    /**
     * Discover all components by scanning for controller.php or template.latte files.
     *
     * @return array<string, array{type: string, namespace?: string, template?: string}> Map of relative path to component info
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
                $templatePath = $path . '/template.latte';

                if (file_exists($controllerPath)) {
                    // Full component with controller
                    $relativePath = $prefix ? $prefix . '/' . $item : $item;
                    $namespace = $this->pathToNamespace($relativePath);
                    $components[$relativePath] = [
                        'type' => 'controller',
                        'namespace' => $namespace,
                    ];
                } elseif (file_exists($templatePath)) {
                    // Template-only component (no controller)
                    $relativePath = $prefix ? $prefix . '/' . $item : $item;
                    $components[$relativePath] = [
                        'type' => 'template',
                        'template' => $templatePath,
                    ];
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
     *
     * @param array{type: string, namespace?: string, template?: string} $info Component info
     */
    private function createComponentFunction(array $info): callable
    {
        if ($info['type'] === 'template') {
            // Template-only component: render directly with Latte
            $templatePath = $info['template'];

            return function (array $props = []) use ($templatePath) {
                if ($this->latte === null) {
                    throw new \RuntimeException('Latte engine not set. Call setLatte() before using template-only components.');
                }
                return new Html($this->latte->renderToString($templatePath, $props));
            };
        }

        // Full component with controller: call the render function
        $renderFn = $info['namespace'] . '\\render';

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
