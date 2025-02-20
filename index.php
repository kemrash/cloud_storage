<?php

use Core\App;
use Core\Db;
use Core\Helper;
use Core\Request;
use Core\Response\PageNotFoundResponse;
use Core\Router;

require_once './autoload.php';

// header('Access-Control-Allow-Origin: http://localhost:5173');
// header('Access-Control-Allow-Credentials: true');
// header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, Authorization');

set_exception_handler([Helper::class, 'exceptionHandler']);

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

$request = new Request();

if (file_exists('./config.php')) {
    Db::getConnection();
}

new App();

App::getService('session')->startSession();

$router = new Router($urlList);
$response = $router->processRequest($request);

if ($response === null) {
    $response = new PageNotFoundResponse();
}

http_response_code($response->getStatusCode());
header($response->getHeader());
echo $response->getData();
