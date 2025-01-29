<?php

use Core\App;
use Core\Db;
use Core\Helper;
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
} catch (Exception $e) {
    Helper::writeLog('index.php' . ': ' .  $e->getMessage());
    http_response_code(500);
    echo 'Произошла ошибка сервера';
}
