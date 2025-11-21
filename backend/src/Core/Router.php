<?php

namespace App\Core;

use App\Core\View;
use App\Core\Security;

class Router {
    private array $routes = [];

    public function get(string $path, callable|array $handler): void {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void {
        $this->add('POST', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): void {
        $this->add('DELETE', $path, $handler);
    }

    private function add(string $method, string $path, callable|array $handler): void {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function dispatch(string $method, string $uri): void {
        $uri = parse_url($uri, PHP_URL_PATH);

        ob_start();

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match('#^' . $route['path'] . '$#', $uri, $matches)) {
                array_shift($matches); // Remove full match

                if ($route['method'] !== 'GET') {
                    Security::requireCsrf();
                }

                call_user_func_array($route['handler'], $matches);

                ob_end_flush();
                return;
            }
        }

        http_response_code(404);
        View::renderLayout('404', ['path' => $uri]);
        ob_end_flush();
    }
}
