<?php

namespace Repositories;

use Core\Config;
use Core\Db;
use Models\User;

class UserRepository extends DB
{
    const DB_NAME = 'user';

    public static function findUsersBy(...$columns): array
    {
        $data = parent::findBy(self::DB_NAME, $columns, Config::getConfig('database.dbColumns.user'));
        $userSystem = Config::getConfig('app.idUserSystem');

        if (isset($data[$userSystem])) {
            unset($data[$userSystem]);
            $data = array_values($data);
        }

        return $data;
    }

    public static function getUserBy(array $params): ?User
    {
        $data = parent::findOneBy(self::DB_NAME, $params, Config::getConfig('database.dbColumns.user'));

        if ($data === null || $data['id'] === Config::getConfig('app.idUserSystem')) {
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

        return parent::updateOneBy(self::DB_NAME, $setParams, ['id' => $user->id], Config::getConfig('database.dbColumns.user'));
    }

    public static function updatePasswordById(int $id, string $passwordEncrypted): void
    {
        parent::updateOneBy(self::DB_NAME, ['passwordEncrypted' => $passwordEncrypted], ['id' => $id], Config::getConfig('database.dbColumns.user'));
    }
}
