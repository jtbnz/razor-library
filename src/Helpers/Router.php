<?php
/**
 * Simple Router for Razor Library
 */

class Router
{
    private array $routes = [];

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, string $handler, array $middleware): void
    {
        // Convert path parameters to regex
        $pattern = preg_replace('/\{([a-zA-Z]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        // Strip base path from URI for matching
        $basePath = config('APP_BASE_PATH', '');
        if ($basePath && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath)) ?: '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run middleware
                foreach ($route['middleware'] as $middleware) {
                    if (!$this->runMiddleware($middleware)) {
                        return;
                    }
                }

                // Call the handler
                $this->callHandler($route['handler'], $params);
                return;
            }
        }

        // No route matched - 404
        $this->notFound();
    }

    private function runMiddleware(string $middleware): bool
    {
        $basePath = config('APP_BASE_PATH', '');

        switch ($middleware) {
            case 'auth':
                if (!isset($_SESSION['user_id'])) {
                    header('Location: ' . $basePath . '/login');
                    exit;
                }
                return true;

            case 'admin':
                if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
                    http_response_code(403);
                    echo view('errors/403');
                    return false;
                }
                return true;

            case 'guest':
                if (isset($_SESSION['user_id'])) {
                    header('Location: ' . $basePath . '/dashboard');
                    exit;
                }
                return true;

            default:
                return true;
        }
    }

    private function callHandler(string $handler, array $params): void
    {
        [$controllerName, $method] = explode('@', $handler);

        $controllerFile = BASE_PATH . '/src/Controllers/' . $controllerName . '.php';
        if (!file_exists($controllerFile)) {
            $this->notFound();
            return;
        }

        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            $this->notFound();
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $method)) {
            $this->notFound();
            return;
        }

        $result = call_user_func_array([$controller, $method], $params);
        if (is_string($result)) {
            echo $result;
        }
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo view('errors/404');
    }
}
