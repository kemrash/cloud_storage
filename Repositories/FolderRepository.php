<?php

namespace Repositories;

use Core\Config;
use Core\Db;
use Models\Folder;

class FolderRepository extends Db
{
    public static function findOneFolderById(int $id): ?Folder
    {
        $data =  Db::findOneBy('folder', ['id' => $id], Config::getConfig('database.dbColumns.folder'));

        if ($data === null) {
            return null;
        }

        return new Folder((int) $data['userId'], (int) $data['parentId'], $data['name'], (int) $data['id']);
    }

    public static function addFolder(Folder $folder): string
    {
        $addFolder = [
            'userId' => $folder->userId,
            'parentId' => $folder->parentId,
            'name' => $folder->name,
        ];

        return parent::insert('folder', $addFolder, Config::getConfig('database.dbColumns.folder'));
    }
}
