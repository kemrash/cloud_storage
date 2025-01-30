<?php

namespace Services;

use Core\App;

class AdminService
{
    public function getUsersList()
    {
        return App::getService('userRepository')::findUsersBy('id', 'email', 'role', 'age', 'gender');
    }

    public function getUserById(string $id): ?array
    {
        $user = App::getService('userRepository')::getUserBy(['id' => (int) $id]);

        if ($user === null) {
            return null;
        }

        return ['id' => $user->id, 'email' => $user->email, 'role' => $user->role, 'age' => $user->age, 'gender' => $user->gender];
    }

    public function deleteUserById(string $id): void
    {
        App::getService('adminRepository')::deleteUserBy(['id' => $id]);
    }
}
