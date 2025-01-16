<?php

use Core\App;
use Core\Db;
use Core\Request;
use Core\Response;
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

parse_str(file_get_contents('php://input'), $PUT);

$request = new Request($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], $_POST, $_GET, $PUT);

Db::getConnection();

new App();

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
