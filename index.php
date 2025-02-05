<?php

use Core\App;
use Core\AppException;
use Core\Db;
use Core\Request;
use Core\Response;
use Core\Router;

require_once './autoload.php';

$urlList = [
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
        'GET' => ['ResetPasswordController', 'preparationResetPassword'],
        'PATCH' => ['ResetPasswordController', 'resetPassword'],
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
    '/files/add' => [
        'POST' => ['FilesController', 'add'],
    ],
    '/' => [
        'GET' => ['IndexController', 'getIndexHtml'],
    ]
];

try {
    $request = new Request();

    Db::getConnection();

    new App();

    App::getService('session')->startSession();

    $router = new Router($urlList);
    $response = $router->processRequest($request);

    if ($response === null) {
        $response = new Response('html', 'Что то пошло не так');
    }

    http_response_code($response->getStatusCode());

    if ($response->getType() === 'json') {
        header('Content-Type: application/json');
    } else if ($response->getHeader() !== '') {
        header($response->getHeader());
    }

    echo $response->getData();
} catch (AppException $e) {
    $e->log();
    http_response_code(500);
    echo 'Произошла ошибка сервера';
}
