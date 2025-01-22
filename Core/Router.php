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

    public function processRequest(Request $request): ?Response
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
            $response = new Response('html', 'Страница не найдена', 404);

            return $response;
        }

        [$controllerClass, $methodName] = $this->routes[$route][$method];

        $controllerClass = 'Controllers\\' . $controllerClass;

        if (!class_exists($controllerClass) || !method_exists($controllerClass, $methodName)) {
            $response = new Response('html', 'Не найден контроллер или экшен', 500);

            return $response;
        }

        $controller = new $controllerClass();

        return (count($params) > 0 ? $controller->$methodName($params, $request) : $controller->$methodName($request)) ?? null;
    }
}
