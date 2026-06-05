<?php

class Router {
    private $routes = [];

    // add get method
    public function get($uri, $action) {
        $this->routes['GET'][$uri] = $action;
    }

    // add post method
    public function post($uri, $action) {
        $this->routes['POST'][$uri] = $action;
    }

    public function dispatch($uri, $method, Controller $controllerTmp) : ?View {
        $uri = trim(parse_url($uri, PHP_URL_PATH), '/');

        // if is it not exists, go 404 page!
        if (!isset($this->routes[$method][$uri])) {
            http_response_code(404);
            return new View('404', '404');
        }

        // get action in map
        $action = $this->routes[$method][$uri];

        // parse request
        list($controllerName, $methodName) = explode('@', $action);

        // get specified control class
        require_once __DIR__ . "/../Controller/$controllerName.php";

        // run control method
        $controller = new $controllerName($controllerTmp);
        return $controller->$methodName();
    }
}
