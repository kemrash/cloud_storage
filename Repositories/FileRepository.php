<?php

namespace Repositories;

use Core\Config;
use Core\Db;
use Models\File;

class FileRepository extends Db
{
    public static function getFilesListUser(int $userId): array
    {
        $data = Db::findBy(
            'file',
            ['id', 'folderId', 'serverName', 'origenName', 'mimeType', 'size'],
            Config::getConfig('database.dbColumns.file'),
            ['userId' => $userId]
        );

        return $data === null ? [] : $data;
    }

    public static function getFilesListFolder(int $folderId): array
    {
        $data = Db::findBy(
            'file',
            ['id', 'folderId', 'serverName', 'origenName', 'mimeType', 'size'],
            Config::getConfig('database.dbColumns.file'),
            ['folderId' => $folderId]
        );

        return $data === null ? [] : $data;
    }

    public static function getFileBy(array $params): ?File
    {
        $data = Db::findOneBy('file', $params, Config::getConfig('database.dbColumns.file'));

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

    public static function deleteFile(int $id): void
    {
        parent::deleteOneBy('file', ['id' => $id], Config::getConfig('database.dbColumns.user'));
    }

    public static function getFileShareList(int $fileId): array
    {
        return parent::findBy('share', ['id', 'userId', 'fileId'], Config::getConfig('database.dbColumns.share'), ['fileId' => $fileId]);
    }

    public static function addUserShareFile(int $userId, int $fileId): void
    {
        parent::insert('share', ['userId' => $userId, 'fileId' => $fileId], Config::getConfig('database.dbColumns.share'));
    }

    public static function getUsersFileShare(int $fileId): array
    {
        return parent::findBy('share', ['userId'], Config::getConfig('database.dbColumns.share'), ['fileId' => $fileId]);
    }

    public static function deleteShareBy(int $shareUserId, int $fileId): void
    {
        parent::deleteOneBy('share', ['userId' => $shareUserId, 'fileId' => $fileId], Config::getConfig('database.dbColumns.share'));
    }
}
