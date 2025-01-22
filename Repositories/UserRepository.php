<?php

namespace Repositories;

use Core\Db;
use Models\User;

class UserRepository extends DB
{
    const DB_NAME = 'user';
    const ALLOWED_COLUMNS = ['id', 'email', 'passwordEncrypted', 'role', 'age', 'gender'];

    public static function findUsersBy(...$columns): array
    {
        return parent::findBy(self::DB_NAME, $columns, self::ALLOWED_COLUMNS);
    }

    public static function getUserBy(array $params): ?User
    {
        $data = parent::findOneBy(self::DB_NAME, $params, self::ALLOWED_COLUMNS);

        if ($data === null) {
            return null;
        }

        return new User($data['id'], $data['email'], $data['passwordEncrypted'], $data['role'], $data['age'], $data['gender']);
    }

    public static function updateUser(User $user): array
    {
        $setParams = [
            'email' => $user->email,
            'passwordEncrypted' => $user->passwordEncrypted,
            'role' => $user->role,
            'age' => $user->age,
            'gender' => $user->gender,
        ];

        return parent::updateOneBy(self::DB_NAME, $setParams, ['id' => $user->id], self::ALLOWED_COLUMNS);
    }
}
