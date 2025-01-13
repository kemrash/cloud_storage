<?php

namespace Controllers;

use Core\Request;
use Core\Response;

class UserController
{
    public function list(Request $request, Response $response): void
    {
        echo 'hello';
    }

    public function get(Request $request, Response $response, array $params): void
    {
        echo 'hello user ' . $params[0] . ' and method ' . $request->getMethod();
    }

    public function update(Request $request, Response $response): void {}
}
