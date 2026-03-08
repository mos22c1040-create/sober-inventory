<?php
namespace App\Core;

class Router {
    protected $routes = [];

    public function add($method, $uri, $controllerAction) {
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'controllerAction' => $controllerAction
        ];
    }

    public function get($uri, $controllerAction) {
        $this->add('GET', $uri, $controllerAction);
    }

    public function post($uri, $controllerAction) {
        $this->add('POST', $uri, $controllerAction);
    }

    public function route($uri, $method) {
        foreach ($this->routes as $route) {
            if ($route['uri'] === $uri && $route['method'] === $method) {
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
        require BASE_PATH . '/views/404.php';
        return;
    }
}
