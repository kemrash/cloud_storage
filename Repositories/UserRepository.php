<?php

namespace Repositories;

use Core\Config;
use Core\Db;
use Models\User;

class UserRepository extends DB
{
    const DB_NAME = 'user';

    /**
     * Находит пользователей по указанным столбцам.
     *
     * @param mixed ...$columns Переменное количество аргументов, представляющих столбцы для поиска.
     * @return array<int, array<string, mixed>> Возвращает массив пользователей, исключая системного пользователя.
     */
    public static function findUsersBy(...$columns): array
    {
        $data = parent::findBy(self::DB_NAME, $columns, Config::getConfig('database.dbColumns.user'));
        $userSystem = Config::getConfig('app.idUserSystem');

        if (isset($data[$userSystem])) {
            unset($data[$userSystem]);
            $data = array_values($data);
        }

        return $data;
    }

    /**
     * Получает пользователя по заданным параметрам.
     *
     * @param array<string, mixed> $params Ассоциативный массив параметров для поиска пользователя.
     * 
     * @return User|null Возвращает объект пользователя или null, если пользователь не найден или является системным пользователем.
     */
    public static function getUserBy(array $params): ?User
    {
        $data = parent::findOneBy(self::DB_NAME, $params, Config::getConfig('database.dbColumns.user'));

        if ($data === null || $data['id'] === Config::getConfig('app.idUserSystem')) {
            return null;
        }

        return new User($data['id'], $data['email'], $data['passwordEncrypted'], $data['role'], $data['age'], $data['gender']);
    }

    /**
     * Обновляет информацию о пользователе в базе данных.
     *
     * @param User $user Объект пользователя, содержащий обновленные данные.
     * 
     * @return array<string, string> Возвращает массив с ключом 'status' и значением 'ok' в случае успешного выполнения, 
     * либо массив с ошибкой в случае ошибки уникальности.
     */
    public static function updateUser(User $user): array
    {
        $setParams = [
            'email' => $user->email,
            'passwordEncrypted' => $user->passwordEncrypted,
            'role' => $user->role,
            'age' => $user->age,
            'gender' => $user->gender,
        ];

        return parent::updateOneBy(self::DB_NAME, $setParams, ['id' => $user->id], Config::getConfig('database.dbColumns.user'));
    }

    /**
     * Обновляет зашифрованный пароль пользователя по его идентификатору.
     *
     * @param int $id Идентификатор пользователя.
     * @param string $passwordEncrypted Зашифрованный пароль пользователя.
     *
     * @return void
     */
    public static function updatePasswordById(int $id, string $passwordEncrypted): void
    {
        parent::updateOneBy(self::DB_NAME, ['passwordEncrypted' => $passwordEncrypted], ['id' => $id], Config::getConfig('database.dbColumns.user'));
    }
}
