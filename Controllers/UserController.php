<?php

namespace Controllers;

use Core\App;
use Core\Request;
use Core\Response;

class UserController
{
    public function list(): Response
    {
        $data = App::getService('userService')->getUsersList();
        $response = new Response('json', json_encode($data));

        return $response;
    }

    public function get(array $params): Response
    {
        $data = App::getService('userService')->findUserById($params[0]);

        if ($data !== null) {
            $response = new Response('json', json_encode($data));
        } else {
            $response = new Response('html', 'Страница не найдена', 404);
        }

        return $response;;
    }

    public function update(): void {}
}
