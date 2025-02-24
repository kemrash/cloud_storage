<?php

namespace Core;

use Core\Request;
use Core\Response;
use Core\Response\PageNotFoundResponse;

class Router
{
    private const ROUTES = [
        '/users/list' => [
            'GET' => ['UserController', 'list'],
        ],
        '/users/get/{id}' => [
            'GET' => ['UserController', 'get'],
        ],
        '/users/update' => [
            'PUT' => ['UserController', 'update'],
        ],
        '/users/login' => [
            'POST' => ['UserController', 'login'],
        ],
        '/users/logout' => [
            'GET' => ['UserController', 'logout'],
        ],
        '/users/reset_password' => [
            'GET' => ['UserController', 'preparationResetPassword'],
            'PATCH' => ['UserController', 'resetPassword'],
        ],
        '/users/search/{email}' => [
            'GET' => ['UserController', 'searchByEmail'],
        ],
        '/admin/users/list' => [
            'GET' => ['AdminController', 'list'],
        ],
        '/admin/users/create' => [
            'POST' => ['AdminController', 'create'],
        ],
        '/admin/users/get/{id}' => [
            'GET' => ['AdminController', 'get'],
        ],
        '/admin/users/delete/{id}' => [
            'DELETE' => ['AdminController', 'delete'],
        ],
        '/admin/users/update/{id}' => [
            'PUT' => ['AdminController', 'update'],
        ],
        '/files/list' => [
            'GET' => ['FilesController', 'list'],
        ],
        '/files/get/{id}' => [
            'GET' => ['FilesController', 'getFile'],
        ],
        '/files/add' => [
            'POST' => ['FilesController', 'add'],
        ],
        '/files/rename' => [
            'PATCH' => ['FilesController', 'rename'],
        ],
        '/files/remove/{id}' => [
            'DELETE' => ['FilesController', 'remove'],
        ],
        '/files/share/{id}' => [
            'GET' => ['FilesController', 'shareList'],
        ],
        '/files/share/{id}/{id}' => [
            'PUT' => ['FilesController', 'addUserShareFile'],
            'DELETE' => ['FilesController', 'deleteUserShareFile'],
        ],
        '/files/download' => [
            'GET' => ['FilesController', 'download'],
        ],
        '/directories/list' => [
            'GET' => ['FolderController', 'list'],
        ],
        '/directories/add' => [
            'POST' => ['FolderController', 'add'],
        ],
        '/directories/rename' => [
            'PATCH' => ['FolderController', 'rename'],
        ],
        '/directories/get/{id}' => [
            'GET' => ['FolderController', 'get'],
        ],
        '/directories/delete/{id}' => [
            'DELETE' => ['FolderController', 'remove'],
        ],
        '/install' => [
            'POST' => ['InstallController', 'install'],
        ],
        '/' => [
            'GET' => ['IndexController', 'getIndexHtml'],
        ],
        '/upload' => [
            'GET' => ['IndexController', 'getIndexHtml'],
        ],
        '/setup' => [
            'GET' => ['IndexController', 'getIndexHtml'],
        ],
    ];

    /**
     * Обрабатывает входящий HTTP-запрос и возвращает соответствующий объект Response.
     * 
     * @param Request $request Объект запроса, содержащий маршрут и метод HTTP.
     * 
     * @return Response|null Возвращает объект Response, если маршрут найден, 
     *                       либо null в случае ошибки.
     * 
     * @throws AppException Если указанный контроллер или его метод не существует.
     * 
     * В зависимости от наличия динамических параметров в маршруте, 
     * метод контроллера вызывается с дополнительными параметрами или без них.
     */
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
            !isset(self::ROUTES[$route][$method]) ||
            !is_array(self::ROUTES[$route][$method]) ||
            count(self::ROUTES[$route][$method]) !== 2
        ) {
            return new PageNotFoundResponse();
        }

        [$controllerClass, $methodName] = self::ROUTES[$route][$method];

        $controllerClass = 'Controllers\\' . $controllerClass;

        if (!class_exists($controllerClass) || !method_exists($controllerClass, $methodName)) {
            throw new AppException(__CLASS__, 'Не найден контроллер или экшен');
        }

        $controller = new $controllerClass();

        return (count($params) > 0 ? $controller->$methodName($params, $request) : $controller->$methodName($request)) ?? null;
    }
}
