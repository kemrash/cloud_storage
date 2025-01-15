<?php

use Core\App;
use Core\Db;
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

if (!is_string($_SERVER['REQUEST_URI']) || !is_string($_SERVER['REQUEST_METHOD'])) {
    http_response_code(500);
    die('Неверно настроен сервер');
}

parse_str(file_get_contents('php://input'), $PUT);

$request = new Request($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], $_POST, $_GET, $PUT);

Db::getConnection();

new App();

$router = new Router($urlList);
$response = $router->processRequest($request);
$response->send();
