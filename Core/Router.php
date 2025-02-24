<?php

namespace Core;

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
     * Обрабатывает входящий запрос и возвращает соответствующий ответ.
     *
     * @param Request $request Входящий HTTP-запрос.
     * @return Response|null Возвращает объект ответа или null, если маршрут не найден.
     * @throws AppException Если контроллер или метод не найдены.
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
            return new Response('renderError', 'Страница не найдена', 404);
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
