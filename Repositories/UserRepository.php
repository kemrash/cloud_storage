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
        return parent::findBy(self::DB_NAME, $columns, Config::getConfig('database.dbColumns.user'));
    }

    public static function getUserBy(array $params): array|User|null
    {
        $data = parent::findOneBy(self::DB_NAME, $params, Config::getConfig('database.dbColumns.user'));

        if ($data['status'] !== 'ok') {
            return $data;
        }

        if ($data['data'] === null) {
            return null;
        }

        return new User($data['data']['id'], $data['data']['email'], $data['data']['passwordEncrypted'], $data['data']['role'], $data['data']['age'], $data['data']['gender']);
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
}
