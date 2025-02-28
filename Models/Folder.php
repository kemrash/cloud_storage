<?php

namespace Models;

use Core\App;
use Core\Config;
use Core\Db;
use Exception;
use PDOException;

class Folder
{
    private const DB_NAME = 'folder';
    private ?int $id;
    private int $userId;
    private int $parentId;
    private string $name;

    /**
     * Магический метод для получения значения свойства объекта.
     *
     * @param string $name Имя свойства, значение которого нужно получить.
     * @return string|int|null Значение свойства, если оно установлено, иначе null.
     */
    public function __get($name): string|int|null
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
    }

    /**
     * Получает список папок для указанного пользователя.
     *
     * @param int $userId Идентификатор пользователя.
     * @return array<int, array<string, mixed>> Массив папок, где каждая папка представлена ассоциативным массивом с ключами 'id' и 'name'.
     */
    public function list(int $userId): array
    {
        return Db::findBy(self::DB_NAME, ['id', 'name'], Config::getConfig('database.dbColumns.folder'), ['userId' => $userId]);
    }

    /**
     * Создает новую папку для пользователя.
     *
     * @param int $userId Идентификатор пользователя.
     * @param int $parentId Идентификатор родительской папки.
     * @param string $name Название папки.
     * @return bool Возвращает true, если папка успешно создана, иначе false.
     * @throws Exception Если произошла ошибка при выполнении запроса к базе данных.
     */
    public function create(int $userId, int $parentId, string $name): bool
    {
        $this->userId = $userId;
        $this->parentId = $parentId;
        $this->name = $name;

        try {
            $addFolder = [
                'userId' => $this->userId,
                'parentId' => $this->parentId,
                'name' => $this->name,
            ];

            $this->id = Db::insert(self::DB_NAME, $addFolder, Config::getConfig('database.dbColumns.folder'));

            return true;
        } catch (PDOException $e) {
            $connection = Db::$connection;

            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            if ($e->getCode() === '23000') {
                return false;
            }

            throw new Exception(__CLASS__ . ': ' . $e->getMessage());
        }
    }

    /**
     * Получает данные папки для указанного пользователя и идентификатора папки.
     *
     * @param int $userId Идентификатор пользователя.
     * @param int $folderId Идентификатор папки.
     * @return bool Возвращает true, если данные папки найдены и установлены, иначе false.
     */
    public function get(int $userId, int $folderId): bool
    {
        $data = Db::findOneBy(self::DB_NAME, ['id' => $folderId, 'userId' => $userId], Config::getConfig('database.dbColumns.folder'));

        if ($data === null) {
            return false;
        }

        $this->id = (int) $data['id'];
        $this->userId = (int) $data['userId'];
        $this->parentId = (int) $data['parentId'];
        $this->name = $data['name'];

        return true;
    }

    /**
     * Переименовывает папку.
     *
     * @param string $name Новое имя папки.
     * 
     * @return bool Возвращает true, если переименование прошло успешно, иначе false.
     */
    public function rename(string $name): bool
    {
        $data = Db::updateOneBy(self::DB_NAME, ['name' => $name], ['id' => $this->id, 'userId' => $this->userId], Config::getConfig('database.dbColumns.folder'));

        if (isset($data['code']) && $data['code'] === '23000') {
            return false;
        }

        $this->name = $name;

        return true;
    }

    /**
     * Удаляет папку и все файлы, находящиеся в ней.
     *
     * Метод сначала получает список файлов в папке, затем начинает транзакцию для удаления записей из базы данных.
     * Если удаление из базы данных прошло успешно, транзакция фиксируется, иначе откатывается.
     * После успешного удаления записей из базы данных, метод удаляет файлы из файлового хранилища.
     *
     * @throws Exception Если произошла ошибка при удалении записей из базы данных.
     */
    public function delete(): void
    {
        $filesList = [];
        $filesList = App::getService('file')->getFilesInFolder($this->id);

        $connection = Db::$connection;
        $connection->beginTransaction();

        try {
            Db::deleteOneBy('file', ['folderId' => $this->id], Config::getConfig('database.dbColumns.file'));
            Db::deleteOneBy(self::DB_NAME, ['id' => $this->id], Config::getConfig('database.dbColumns.folder'));

            $connection->commit();
        } catch (PDOException $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw new Exception(__CLASS__ . ': ' . $e->getMessage());
        }

        if (count($filesList) === 0) {
            return;
        }

        App::getService('fileStorage')->deleteFiles($filesList);
    }

    /**
     * Получает список папок внутри заданной родительской папки.
     *
     * @return array<int, array<string, string|int>> Массив папок, где каждая папка представлена в виде ассоциативного массива с ключами 'id' и 'name'.
     */
    public function getFoldersInFolder(): array
    {
        return Db::findBy(self::DB_NAME, ['id', 'name'], Config::getConfig('database.dbColumns.folder'), ['parentId' => $this->id]);
    }
}
