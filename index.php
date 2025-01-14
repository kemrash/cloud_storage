<?php

use Core\App;
use Core\Request;
use Core\Router;
use Controllers\UserController;

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

$foldersPathRepositoriesAndServices = ['Repositories', 'Services'];

if (!is_string($_SERVER['REQUEST_URI']) || !is_string($_SERVER['REQUEST_METHOD'])) {
    http_response_code(500);
    die('Неверно настроен сервер');
}

new Request($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

$router = new Router($urlList);
$router->processRequest();

new App($foldersPathRepositoriesAndServices);
