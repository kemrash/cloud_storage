<?php

namespace Repositories;

use Core\Config;
use Core\Db;
use Models\File;
use Models\Folder;

class FileRepository extends Db
{
    public static function getFilesList(int $userId): array
    {
        $data = Db::findBy('file', ['id', 'folderId', 'origenName', 'mimeType', 'size'],  Config::getConfig('database.dbColumns.file'), ['userId' => $userId]);

        return $data === null ? [] : $data;
    }

    public static function getFileById(int $id): ?File
    {
        $data = Db::findOneBy('file', ['id' => $id], Config::getConfig('database.dbColumns.file'));

        if ($data === null) {
            return null;
        }

        return new File($data['userId'], $data['folderId'], $data['serverName'], $data['origenName'], $data['mimeType'], $data['size'], $data['id']);
    }

    public static function addFile(File $file): string
    {
        $addFile = [
            'userId' => $file->userId,
            'folderId' => $file->folderId,
            'serverName' => $file->serverName,
            'origenName' => $file->origenName,
            'mimeType' => $file->mimeType,
            'size' => $file->size,
        ];

        return Db::insert('file', $addFile, Config::getConfig('database.dbColumns.file'));
    }

    public static function renameFile(int $id, string $origenName): void
    {
        parent::updateOneBy('file', ['origenName' => $origenName], ['id' => $id], Config::getConfig('database.dbColumns.file'));
    }

    public static function deleteFile(int $id)
    {
        parent::deleteOneBy('file', ['id' => $id], Config::getConfig('database.dbColumns.user'));
    }

    public static function findOneFolderById(int $id): ?Folder
    {
        $data =  Db::findOneBy('folder', ['id' => $id], Config::getConfig('database.dbColumns.folder'));

        if ($data === null) {
            return null;
        }

        return new Folder((int) $data['id'], (int) $data['userId'], (int) $data['parentId'], $data['name']);
    }
}
