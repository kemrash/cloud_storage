<?php

namespace Controllers;

use Core\App;
use Core\Request;
use Core\JSONResponse;

class UserController
{
    public function list(): JSONResponse
    {
        $data = App::getService('userService')->getList();

        $response = new JSONResponse();
        $response->setHeaders('Content-Type: application/json');
        $response->setData(json_encode($data));

        return $response;
    }

    public function get(Request $request, array $params): void {}

    public function update(): void {}
}
