<?php

namespace Repositories;

use Core\Config;
use Core\Db;
use Models\File;

class FileRepository extends Db
{
    /**
     * Получает список файлов пользователя по его идентификатору.
     *
     * @param int $userId Идентификатор пользователя.
     * @return array<int, array<string, int|string>> Массив файлов, где каждый файл представлен в виде ассоциативного массива с ключами:
     * - 'id' (int): Идентификатор файла.
     * - 'folderId' (int): Идентификатор папки.
     * - 'serverName' (string): Имя файла на сервере.
     * - 'origenName' (string): Оригинальное имя файла.
     * - 'mimeType' (string): MIME-тип файла.
     * - 'size' (int): Размер файла.
     */
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

    /**
     * Получает список файлов в указанной папке.
     *
     * @param int $folderId Идентификатор папки.
     * @return array<array{id: int, folderId: int, serverName: string, origenName: string, mimeType: string, size: int}> Массив файлов,
     * содержащий информацию о каждом файле.
     */
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

    /**
     * Получает файл по заданным параметрам.
     *
     * @param array<string, mixed> $params Ассоциативный массив параметров для поиска файла.
     * 
     * @return File|null Возвращает объект File, если файл найден, или null, если файл не найден.
     */
    public static function getFileBy(array $params): ?File
    {
        $data = Db::findOneBy('file', $params, Config::getConfig('database.dbColumns.file'));

        if ($data === null) {
            return null;
        }

        return new File($data['userId'], $data['folderId'], $data['serverName'], $data['origenName'], $data['mimeType'], $data['size'], $data['id']);
    }

    /**
     * Добавляет файл в базу данных.
     *
     * @param File $file Объект файла, содержащий информацию о файле.
     * 
     * @return string Возвращает идентификатор добавленного файла.
     */
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

    /**
     * Переименовывает файл в базе данных.
     *
     * @param int $id Идентификатор файла.
     * @param string $origenName Новое имя файла.
     *
     * @return void
     */
    public static function renameFile(int $id, string $origenName): void
    {
        parent::updateOneBy('file', ['origenName' => $origenName], ['id' => $id], Config::getConfig('database.dbColumns.file'));
    }

    /**
     * Удаляет файл по его идентификатору.
     *
     * @param int $id Идентификатор файла, который нужно удалить.
     *
     * @return void
     */
    public static function deleteFile(int $id): void
    {
        parent::deleteOneBy('file', ['id' => $id], Config::getConfig('database.dbColumns.user'));
    }

    /**
     * Возвращает список пользователей, с которыми был поделён файл.
     *
     * @param int $fileId Идентификатор файла.
     * @return array<int, array<string, mixed>> Массив данных о пользователях, с которыми был поделён файл.
     */
    public static function getFileShareList(int $fileId): array
    {
        return parent::findBy('share', ['id', 'userId', 'fileId'], Config::getConfig('database.dbColumns.share'), ['fileId' => $fileId]);
    }

    /**
     * Добавляет запись о совместном использовании файла пользователем.
     *
     * @param int $userId Идентификатор пользователя.
     * @param int $fileId Идентификатор файла.
     *
     * @return void
     */
    public static function addUserShareFile(int $userId, int $fileId): void
    {
        parent::insert('share', ['userId' => $userId, 'fileId' => $fileId], Config::getConfig('database.dbColumns.share'));
    }

    /**
     * Получает список пользователей, с которыми был поделён файл.
     *
     * @param int $fileId Идентификатор файла.
     * @return array<int, array<string, mixed>> Массив данных о пользователях, с которыми был поделён файл.
     */
    public static function getUsersFileShare(int $fileId): array
    {
        return parent::findBy('share', ['userId'], Config::getConfig('database.dbColumns.share'), ['fileId' => $fileId]);
    }

    /**
     * Удаляет запись о доступе к файлу для указанного пользователя.
     *
     * @param int $shareUserId Идентификатор пользователя, у которого нужно удалить доступ.
     * @param int $fileId Идентификатор файла, доступ к которому нужно удалить.
     *
     * @return void
     */
    public static function deleteShareBy(int $shareUserId, int $fileId): void
    {
        parent::deleteOneBy('share', ['userId' => $shareUserId, 'fileId' => $fileId], Config::getConfig('database.dbColumns.share'));
    }
}
