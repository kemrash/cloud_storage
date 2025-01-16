<?php

namespace Services;

use Core\App;

class UserService
{
    public function getUsersList(): array
    {
        return App::getService('userRepository')::findBy('role', 'age', 'gender');
    }
}
