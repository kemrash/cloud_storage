<?php

namespace Repositories;

use Core\AppException;
use Core\Config;
use Core\Db;
use Exception;
use Models\Folder;

class FolderRepository extends Db
{
    public static function findOneFolderById(int $id): ?Folder
    {
        $data = Db::findOneBy('folder', ['id' => $id], Config::getConfig('database.dbColumns.folder'));

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

    public static function renameFolder(int $userId, int $id, string $name): array
    {
        $connection = parent::$connection;
        $connection->beginTransaction();

        try {
            $folder = self::getFolderBy($userId, $id);

            if ($folder === null) {
                $connection->rollBack();

                return [
                    'status' => 'error',
                    'code' => 404,
                    'data' => '404 Not Found',
                ];
            }

            Db::updateOneBy('folder', ['name' => $name], ['id' => $id, 'userId' => $userId], Config::getConfig('database.dbColumns.folder'));

            $connection->commit();

            return ['status' => 'ok'];
        } catch (Exception $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw new AppException(__CLASS__, $e->getMessage());
        }
    }

    public static function getFolderBy(int $userId, int $folderId): ?Folder
    {
        $folder = Db::findOneBy('folder', ['id' => $folderId, 'userId' => $userId], Config::getConfig('database.dbColumns.folder'));

        if ($folder === null) {
            return null;
        }

        return new Folder($folder['userId'], $folder['parentId'], $folder['name'], $folder['id']);
    }

    public static function deleteFilesAndFolderByFolderId(int $id): void
    {
        Db::deleteOneBy('file', ['folderId' => $id], Config::getConfig('database.dbColumns.file'));
        Db::deleteOneBy('folder', ['id' => $id], Config::getConfig('database.dbColumns.folder'));
    }
}
