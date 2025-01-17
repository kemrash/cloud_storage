<?php

namespace Services;

use Core\App;

class UserService
{
    public function getUsersList(): array
    {
        return App::getService('userRepository')::findUsersBy('role', 'age', 'gender');
    }

    public function findUserById(string $id): ?array
    {
        return App::getService('userRepository')::findOneUserBy(['id' => (int) $id]);
    }
}
