<?php

namespace Repositories;

use Core\App;
use Core\Config;
use Core\Db;
use Models\Folder;

class AdminRepository  extends Db
{
    public static function createUser(array $params): array
    {
        $connection = Db::$connection;
        $connection->beginTransaction();

        $id = parent::insert('user', $params, Config::getConfig('database.dbColumns.user'));
        $folder = new Folder((int) $id, Config::getConfig('app.idUserSystem'), 'home');

        $folderId = App::getService('folderRepository')::addFolder($folder);

        $connection->commit();

        return [
            'status' => 'ok',
            'id' => $id,
            'folderId' => $folderId,
        ];
    }

    public static function deleteUserBy(array $params): void
    {
        parent::deleteOneBy('user', $params, Config::getConfig('database.dbColumns.user'));
    }
}
