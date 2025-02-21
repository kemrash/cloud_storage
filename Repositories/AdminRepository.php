<?php

namespace Repositories;

use Core\App;
use Core\Config;
use Core\Db;
use Models\Folder;

class AdminRepository extends Db
{
    /**
     * Создает нового пользователя и его домашнюю папку.
     *
     * @param array<string, mixed> $params Ассоциативный массив параметров для создания пользователя.
     * 
     * @return array{
     *     status: string,
     *     id: int,
     *     folderId: int
     * } Возвращает массив с результатом операции, включающий статус, ID пользователя и ID папки.
     */
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

    /**
     * Удаляет пользователя по заданным параметрам.
     *
     * @param array<string, mixed> $params Ассоциативный массив параметров для удаления пользователя.
     *
     * @return void
     */
    public static function deleteUserBy(array $params): void
    {
        parent::deleteOneBy('user', $params, Config::getConfig('database.dbColumns.user'));
    }
}
