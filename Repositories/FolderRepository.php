<?php

namespace Repositories;

use Core\AppException;
use Core\Config;
use Core\Db;
use Exception;
use Models\Folder;

class FolderRepository extends Db
{
    /**
     * Находит папку по её идентификатору.
     *
     * @param int $id Идентификатор папки.
     * @return Folder|null Возвращает объект Folder, если папка найдена, или null, если папка не найдена.
     */
    public static function findOneFolderById(int $id): ?Folder
    {
        $data = Db::findOneBy('folder', ['id' => $id], Config::getConfig('database.dbColumns.folder'));

        if ($data === null) {
            return null;
        }

        return new Folder((int) $data['userId'], (int) $data['parentId'], $data['name'], (int) $data['id']);
    }

    /**
     * Добавляет новую папку в базу данных.
     *
     * @param Folder $folder Объект папки, содержащий данные для добавления.
     * 
     * @return string Возвращает идентификатор добавленной папки.
     */
    public static function addFolder(Folder $folder): string
    {
        $addFolder = [
            'userId' => $folder->userId,
            'parentId' => $folder->parentId,
            'name' => $folder->name,
        ];

        return parent::insert('folder', $addFolder, Config::getConfig('database.dbColumns.folder'));
    }

    /**
     * Переименовывает папку пользователя.
     *
     * @param int $userId Идентификатор пользователя.
     * @param int $id Идентификатор папки.
     * @param string $name Новое имя папки.
     * @return array{
     *     status: string,
     *     code?: int,
     *     data?: string
     * } Возвращает массив с результатом операции. В случае успеха возвращает ['status' => 'ok'].
     * В случае ошибки возвращает ['status' => 'error', 'code' => 404, 'data' => '404 Not Found'].
     * @throws AppException В случае возникновения исключения во время выполнения операции.
     */
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

    /**
     * Получает папку по идентификатору пользователя и идентификатору папки.
     *
     * @param int $userId Идентификатор пользователя.
     * @param int $folderId Идентификатор папки.
     * @return Folder|null Возвращает объект Folder, если папка найдена, иначе null.
     */
    public static function getFolderBy(int $userId, int $folderId): ?Folder
    {
        $folder = Db::findOneBy('folder', ['id' => $folderId, 'userId' => $userId], Config::getConfig('database.dbColumns.folder'));

        if ($folder === null) {
            return null;
        }

        return new Folder($folder['userId'], $folder['parentId'], $folder['name'], $folder['id']);
    }

    /**
     * Возвращает список папок пользователя по его идентификатору.
     *
     * @param int $id Идентификатор пользователя.
     * @return array<int, array<string, mixed>> Массив папок пользователя, где каждый элемент является ассоциативным массивом с ключами 'id' и 'name'.
     */
    public static function getUserFoldersList(int $id): array
    {
        return Db::findBy('folder', ['id', 'name'], Config::getConfig('database.dbColumns.folder'), ['userId' => $id]);
    }

    /**
     * Удаляет файлы и папку по идентификатору папки.
     *
     * @param int $id Идентификатор папки.
     *
     * @return void
     */
    public static function deleteFilesAndFolderByFolderId(int $id): void
    {
        Db::deleteOneBy('file', ['folderId' => $id], Config::getConfig('database.dbColumns.file'));
        Db::deleteOneBy('folder', ['id' => $id], Config::getConfig('database.dbColumns.folder'));
    }
}
