<?php

namespace Repositories;

use Core\Config;
use Core\Db;

class AdminRepository  extends Db
{
    public static function deleteUserBy(array $params): void
    {
        parent::deleteOneBy('user', $params, Config::getConfig('database.dbColumns.user'));
    }
}
