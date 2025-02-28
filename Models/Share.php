<?php

namespace Models;

use Core\Config;
use Core\Db;
use Exception;
use PDOException;

class Share
{
    private const DB_NAME = 'share';
    private int $id;
    private int $userId;
    private int $fileId;

    /**
     * Возвращает список записей из базы данных для указанного файла.
     *
     * @param int $fileId Идентификатор файла.
     * @return array<int, array<string, int>> Массив записей, где каждая запись представлена в виде ассоциативного массива.
     */
    public function list(int $fileId): array
    {
        return Db::findBy(self::DB_NAME, ['id', 'userId', 'fileId'], Config::getConfig('database.dbColumns.share'), ['fileId' => $fileId]);
    }

    public static function getUsers(int $fileId): array
    {
        return Db::findBy(self::DB_NAME, ['userId'], Config::getConfig('database.dbColumns.share'), ['fileId' => $fileId]);
    }

    /**
     * Создает запись о совместном доступе к файлу для пользователя.
     *
     * @param int $userId Идентификатор пользователя.
     * @param int $fileId Идентификатор файла.
     * @return bool Возвращает true, если запись успешно создана, иначе false.
     * @throws Exception Если возникает ошибка при выполнении запроса, кроме ошибки с кодом '23000'.
     */
    public function create(int $userId, int $fileId): bool
    {
        $this->userId = $userId;
        $this->fileId = $fileId;

        try {
            $this->id = Db::insert('share', ['userId' => $this->userId, 'fileId' => $this->fileId], Config::getConfig('database.dbColumns.share'));

            return true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }

            throw new Exception(__CLASS__ . ': ' . $e->getMessage());
        }
    }

    /**
     * Удаляет запись о доступе к файлу для указанного пользователя.
     *
     * @param int $shareUserId Идентификатор пользователя, с которым был разделен файл.
     * @param int $fileId Идентификатор файла, доступ к которому нужно удалить.
     *
     * @return void
     */
    public function delete(int $shareUserId, int $fileId)
    {
        Db::deleteOneBy(self::DB_NAME, ['userId' => $shareUserId, 'fileId' => $fileId], Config::getConfig('database.dbColumns.share'));
    }
}
