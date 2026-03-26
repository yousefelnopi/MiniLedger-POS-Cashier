<?php

namespace App\Core;

class Router
{
    // Stores all registered routes
    private array $routes = [];

    // Base path on XAMPP
    private string $basePath;

    public function __construct()
    {
        $this->basePath = Config::get('base_url', '');
    }

    // ─── Register GET route ───────────────────────────────────────────
    public function get(string $path, array $handler): void
    {
        $this->routes[] = [
            'method'  => 'GET',
            'path'    => $path,
            'handler' => $handler,
        ];
    }

    // ─── Register POST route ──────────────────────────────────────────
    public function post(string $path, array $handler): void
    {
        $this->routes[] = [
            'method'  => 'POST',
            'path'    => $path,
            'handler' => $handler,
        ];
    }

    // ─── Dispatch — find matching route and run it ────────────────────
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];

        // Get URI and remove base path
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $uri = '/' . trim(str_replace($this->basePath, '', $uri), '/');
        
        if ($uri === '') $uri = '/';

        foreach ($this->routes as $route) {

            // Convert {id} to regex pattern
            $pattern = preg_replace('/\{[a-z_]+\}/', '([^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                array_shift($matches);

                [$controllerClass, $action] = $route['handler'];

                // Check controller exists
                if (!class_exists($controllerClass)) {
                    $this->abort(500, "Controller [{$controllerClass}] not found.");
                    return;
                }

                $controller = new $controllerClass();

                // Check method exists
                if (!method_exists($controller, $action)) {
                    $this->abort(500, "Method [{$action}] not found.");
                    return;
                }

                // Run the controller method
                call_user_func_array([$controller, $action], $matches);
                return;
            }
        }

        // No matching route found
        $this->abort(404, "Route [{$method} {$uri}] not found.");
    }

    // ─── Abort — return error response ───────────────────────────────
    private function abort(int $code, string $message): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $message]);
    }
}
