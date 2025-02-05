<?php

namespace Repositories;

use Core\Config;
use Core\Db;

class AdminRepository  extends Db
{
    public static function createUser(array $params): void
    {
        $id = parent::insert('user', $params, Config::getConfig('database.dbColumns.user'));
        $folder = [
            'userId' => (int) $id,
            'parentId' => Config::getConfig('app.idUserSystem'),
            'name' => 'home',
        ];

        parent::insert('folder', $folder, Config::getConfig('database.dbColumns.folder'));
    }

    public static function deleteUserBy(array $params): void
    {
        parent::deleteOneBy('user', $params, Config::getConfig('database.dbColumns.user'));
    }
}
