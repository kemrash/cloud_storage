<?php

namespace Services;

use Core\App;

class UserService
{
    public function getList(): array
    {
        return App::getService('userRepository')::findBy('role', 'age', 'gender');
    }
}
