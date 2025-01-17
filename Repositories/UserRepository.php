<?php

namespace Repositories;

use Core\Db;

class UserRepository extends DB
{
    const DB_NAME = 'user';
    const ALLOWED_COLUMNS = ['id', 'login', 'password_encrypted', 'role', 'age', 'gender'];

    public static function findUsersBy(...$columns): array
    {
        return parent::findBy(self::DB_NAME, $columns, self::ALLOWED_COLUMNS);
    }

    public static function findOneUserBy(array $params)
    {
        return parent::findOneBy(self::DB_NAME, $params, self::ALLOWED_COLUMNS);
    }
}
