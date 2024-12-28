<?php

// use Controllers\User;

require_once './autoload.php';

// $urlList = [
//     '/users/list' => [
//         'GET' => [User::class, 'getUsersList'],
//     ],
// ];

if (
    is_string($_SERVER['REQUEST_URI']) &&
    isset($urlList[$_SERVER['REQUEST_URI']]) &&
    isset($_SERVER['REQUEST_METHOD']) &&
    is_string($_SERVER['REQUEST_METHOD']) &&
    isset($urlList[$_SERVER['REQUEST_URI']][$_SERVER['REQUEST_METHOD']])
) {
    [$controllerClass, $methodName] = $urlList[$_SERVER['REQUEST_URI']][$_SERVER['REQUEST_METHOD']];

    if (class_exists($controllerClass) && method_exists($controllerClass, $methodName)) {
        (new $controllerClass)->$methodName();
    } else {
        http_response_code(500);
        echo 'Не найден класс контролера';
    }
} else {
    http_response_code(404);
    echo 'Страница не найдена';
}
