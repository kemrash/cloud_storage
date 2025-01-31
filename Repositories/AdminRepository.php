<?php

namespace Repositories;

use Core\Config;
use Core\Db;

class AdminRepository  extends Db
{
    private const DB_NAME = 'user';

    public static function createUser(array $params): void
    {
        parent::insert(self::DB_NAME, $params, Config::getConfig('database.dbColumns.user'));
    }

    public static function deleteUserBy(array $params): void
    {
        parent::deleteOneBy(self::DB_NAME, $params, Config::getConfig('database.dbColumns.user'));
    }
}
