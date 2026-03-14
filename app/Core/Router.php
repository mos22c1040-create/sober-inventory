<?php

namespace App\Core;

class Router {
    protected $routes = [];

    public function add($method, $uri, $controllerAction, $middleware = null) {
        $this->routes[] = [
            'method'      => $method,
            'uri'        => $uri,
            'controllerAction' => $controllerAction,
            'middleware' => $middleware
        ];
    }

    public function get($uri, $controllerAction, $middleware = null) {
        $this->add('GET', $uri, $controllerAction, $middleware);
    }

    public function post($uri, $controllerAction, $middleware = null) {
        $this->add('POST', $uri, $controllerAction, $middleware);
    }

    public function route($uri, $method) {
        foreach ($this->routes as $route) {
            if ($route['uri'] === $uri && $route['method'] === $method) {
                // Run middleware if defined
                if (!empty($route['middleware']) && is_callable($route['middleware'])) {
                    call_user_func($route['middleware']);
                }

                list($controller, $action) = explode('@', $route['controllerAction']);
                $controllerClass = "App\\Controllers\\{$controller}";
                
                if (class_exists($controllerClass) && method_exists($controllerClass, $action)) {
                    $instance = new $controllerClass();
                    return call_user_func([$instance, $action]);
                }
            }
        }
        
        // Return a 404 response
        http_response_code(404);
        if (file_exists(BASE_PATH . '/views/404.php')) {
            require BASE_PATH . '/views/404.php';
        }
        return;
    }
}