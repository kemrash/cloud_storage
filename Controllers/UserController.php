<?php

namespace Controllers;

use Core\App;
use Core\Request;
use Core\ResponseFactory;
use Core\Response\Response;

class UserController
{
    public function list(): Response
    {
        $data = App::getService('userService')->getList();
        $response = ResponseFactory::createResponse('json', json_encode($data));

        return $response;
    }

    public function get(Request $request, array $params): void {}

    public function update(): void {}
}
