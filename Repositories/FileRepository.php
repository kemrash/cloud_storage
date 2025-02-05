<?php

namespace Repositories;

use Core\Config;
use Core\Db;
use Models\Folder;

class FileRepository extends Db
{
    public static function findOneFolderById(int $id): ?Folder
    {
        $data =  Db::findOneBy('folder', ['id' => $id], Config::getConfig('database.dbColumns.file'));

        if ($data === null) {
            return null;
        }

        return new Folder((int) $data['id'], (int) $data['userId'], (int) $data['parentId'], $data['name']);
    }

    public static function addFile(int $userId, int $folderId, string $name, array $file): string
    {
        $addFile = [
            'userId' => $userId,
            'folderId' => $folderId,
            'serverName' => $name,
            'origenName' => $file['name'],
            'mimeType' => $file['type'],
            'size' => $file['size'],
        ];

        return Db::insert('file', $addFile, Config::getConfig('database.dbColumns.file'));
    }

    public static function deleteFile(int $id)
    {
        parent::deleteOneBy('file', ['id' => $id], Config::getConfig('database.dbColumns.user'));
    }
}
