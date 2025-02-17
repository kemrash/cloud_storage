<?php

namespace Core;

use Core\Request;
use Core\Response;
use Core\Response\PageNotFoundResponse;

class Router
{
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
                $unit = '{id}';
            }

            if (filter_var($unit, FILTER_VALIDATE_EMAIL)) {
                $params[] = $unit;
                $unit = '{email}';
            }
        }

        $route = implode('/', $unitsRoute);

        if (
            !isset($this->routes[$route][$method]) ||
            !is_array($this->routes[$route][$method]) ||
            count($this->routes[$route][$method]) !== 2
        ) {
            $response = new PageNotFoundResponse();

            return $response;
        }

        [$controllerClass, $methodName] = $this->routes[$route][$method];

        $controllerClass = 'Controllers\\' . $controllerClass;

        if (!class_exists($controllerClass) || !method_exists($controllerClass, $methodName)) {
            throw new AppException(__CLASS__, 'Не найден контроллер или экшен');
        }

        $controller = new $controllerClass();

        return (count($params) > 0 ? $controller->$methodName($params, $request) : $controller->$methodName($request)) ?? null;
    }
}
