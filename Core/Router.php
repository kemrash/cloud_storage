<?php

namespace Core;

use Core\Request;
use Core\Response;

class Router
{
    private const PARAMETER = '{id}';
    private array $routes;

    function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function processRequest(Request $request, Response $response): void
    {
        $params = [];
        $route = $request->getRoute();
        $method = $request->getMethod();
        $unitsRoute = explode('/', $route);

        foreach ($unitsRoute as &$unit) {
            if (preg_match('/^\d+$/', (string) $unit) === 1) {
                $params[] = $unit;
                $unit = self::PARAMETER;
            }
        }

        $route = implode('/', $unitsRoute);

        if (
            !isset($this->routes[$route][$method]) ||
            !is_array($this->routes[$route][$method]) ||
            count($this->routes[$route][$method]) !== 2
        ) {
            http_response_code(404);
            die('Страница не найдена');
        }

        [$controllerClass, $methodName] = $this->routes[$route][$method];

        if (!class_exists($controllerClass) || !method_exists($controllerClass, $methodName)) {
            http_response_code(500);
            die('Не найден контроллер или экшен');
        }

        $controller = new $controllerClass();

        if (count($params) > 0) {
            $controller->$methodName($request, $response, $params);
        } else {
            $controller->$methodName($request, $response);
        }
    }
}
