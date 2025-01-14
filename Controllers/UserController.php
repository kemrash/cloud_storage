<?php

namespace Controllers;

use Core\App;

class UserController
{
    public function list(): void
    {
        $data = 'hello';
        echo $data;
    }

    public function get(array $params): void
    {
        $data = 'hello user ' . $params[0] . ' and method ' . App::getService('request')->getMethod() . '<br>';
        echo $data;
    }

    public function update(): void {}
}
