<?php
/**
 * Main Application Class
 * 
 * Handles routing, middleware, and request processing
 */

class App 
{
    private $routes = [];
    private $middleware = [];
    private $request;
    private $response;

    public function __construct() 
    {
        $this->request = new Request();
        $this->response = new Response();
        $this->loadRoutes();
    }

    private function loadRoutes(): void 
    {
        // Load route definitions
        require_once __DIR__ . '/../../routes/web.php';
    }

    public function get(string $path, $handler): void 
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, $handler): void 
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, $handler): void 
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, $handler): void 
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, $handler): void 
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
        ];
    }

    public function middleware($middleware): self 
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function run(): void 
    {
        try {
            $requestMethod = $_SERVER['REQUEST_METHOD'];
            $requestPath = $this->getRequestPath();

            // Find matching route
            $route = $this->findRoute($requestMethod, $requestPath);
            
            if (!$route) {
                $this->handleNotFound();
                return;
            }

            // Run middleware
            foreach ($this->middleware as $middleware) {
                if (!$this->runMiddleware($middleware)) {
                    return;
                }
            }

            // Execute route handler
            $this->executeHandler($route['handler'], $route['params'] ?? []);

        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    private function getRequestPath(): string 
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($path, PHP_URL_PATH);
        return '/' . trim($path, '/');
    }

    private function findRoute(string $method, string $path): ?array 
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->convertRouteToRegex($route['path']);
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches); // Remove full match
                return [
                    'handler' => $route['handler'],
                    'params' => $matches,
                ];
            }
        }

        return null;
    }

    private function convertRouteToRegex(string $route): string 
    {
        $route = preg_replace('/\{([^}]+)\}/', '([^/]+)', $route);
        return '#^' . $route . '$#';
    }

    private function runMiddleware($middleware): bool 
    {
        if (is_string($middleware)) {
            $middleware = new $middleware();
        }

        return $middleware->handle($this->request, $this->response);
    }

    private function executeHandler($handler, array $params = []): void 
    {
        if (is_array($handler)) {
            [$controller, $method] = $handler;
            $controllerInstance = new $controller();
            call_user_func_array([$controllerInstance, $method], $params);
        } elseif (is_callable($handler)) {
            call_user_func_array($handler, $params);
        } else {
            throw new Exception("Invalid route handler");
        }
    }

    private function handleNotFound(): void 
    {
        http_response_code(404);
        include __DIR__ . '/../../resources/views/errors/404.php';
    }

    private function handleError(Exception $e): void 
    {
        error_log("Application Error: " . $e->getMessage());
        
        if (config('app.debug')) {
            echo "<h1>Error</h1>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        } else {
            http_response_code(500);
            include __DIR__ . '/../../resources/views/errors/500.php';
        }
    }
}

/**
 * Simple Request class
 */
class Request 
{
    public function method(): string 
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function path(): string 
    {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    public function input(string $key, $default = null) 
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public function all(): array 
    {
        return array_merge($_GET, $_POST);
    }

    public function has(string $key): bool 
    {
        return isset($_POST[$key]) || isset($_GET[$key]);
    }

    public function isMethod(string $method): bool 
    {
        return $this->method() === strtoupper($method);
    }
}

/**
 * Simple Response class
 */
class Response 
{
    public function json(array $data, int $status = 200): void 
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function redirect(string $url): void 
    {
        header("Location: $url");
        exit;
    }

    public function back(): void 
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? url();
        $this->redirect($referer);
    }

    public function view(string $view, array $data = []): void 
    {
        extract($data);
        include __DIR__ . "/../../resources/views/$view.php";
    }
}