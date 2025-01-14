<?php

namespace Controllers;

use Core\App;
use Core\Request;

class UserController
{
    public function list(): void
    {
        $data = 'hello';
        echo $data;
    }

    public function get(array $params): void
    {
        $data = 'hello user ' . $params[0] . ' and method ' . Request::getMethod();
        echo $data;
    }

    public function update(): void {}
}
