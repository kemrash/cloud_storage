<?php

use Controllers\UserController;
use Core\Request;
use Core\Response;
use Core\Router;

require_once './autoload.php';

$urlList = [
    '/users/list' => [
        'GET' => [UserController::class, 'list'],
    ],
    '/users/get/{id}' => [
        'GET' => [UserController::class, 'get'],
    ],
    '/users/update' => [
        'PUT' => [UserController::class, 'update'],
    ]
];

if (!is_string($_SERVER['REQUEST_URI']) || !is_string($_SERVER['REQUEST_METHOD'])) {
    http_response_code(500);
    die('Неверно настроен сервер');
}

$router = new Router($urlList);
$request = new Request($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
$response = new Response();

$router->processRequest($request, $response);
