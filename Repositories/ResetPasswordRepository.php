<?php

namespace Repositories;

use Core\AppException;
use Core\Config;
use Core\Db;
use Core\Helper;
use Exception;
use Models\ResetPassword;
use PDO;

class ResetPasswordRepository extends Db
{
    const DB_NAME = 'reset_password';

    /**
     * Создает запись для сброса пароля в базе данных.
     *
     * @param ResetPassword $resetPassword Объект, содержащий данные для сброса пароля.
     *
     * @throws AppException Если возникает ошибка при выполнении запроса к базе данных.
     *
     * @return void
     */
    public static function createdResetPassword(ResetPassword $resetPassword): void
    {
        $statement = parent::$connection->prepare("
            INSERT INTO " . self::DB_NAME . " (userId, hashedToken, expiresAt, createdAt)
            VALUES (:userId, :hashedToken, :expiresAt, :createdAt)
        ");

        $statement->bindValue('userId', $resetPassword->userId, PDO::PARAM_INT);
        $statement->bindValue('hashedToken', $resetPassword->hashedToken, PDO::PARAM_STR);
        $statement->bindValue('expiresAt', $resetPassword->expiresAt, PDO::PARAM_STR);
        $statement->bindValue('createdAt', $resetPassword->createdAt, PDO::PARAM_STR);

        try {
            $statement->execute();
        } catch (Exception $e) {
            throw new AppException(__CLASS__, $e->getMessage());
        }
    }

    /**
     * Получает объект ResetPassword по заданным параметрам.
     *
     * @param array<string, mixed> $params Ассоциативный массив параметров для поиска.
     * 
     * @return ?ResetPassword Возвращает объект ResetPassword или null, если данные не найдены.
     */
    public static function getResetPasswordBy(array $params): ?ResetPassword
    {
        $data = parent::findOneBy(self::DB_NAME, $params, Config::getConfig('database.dbColumns.reset_password'));

        if ($data === null) {
            return null;
        }

        return new ResetPassword($data['id'], $data['hashedToken'], $data['expiresAt'], $data['createdAt'], $data['id']);
    }

    /**
     * Удаляет старые записи сброса пароля, срок действия которых истек.
     *
     * @param string $currentTime Текущее время в формате строки, с которым сравниваются сроки действия записей.
     *
     * @throws AppException Если возникает ошибка при выполнении запроса к базе данных.
     *
     * @return void
     */
    public static function clearOldResetPassword(string $currentTime): void
    {
        $statement = parent::$connection->prepare('DELETE FROM ' . self::DB_NAME . ' WHERE expiresAt < :currentTime');
        $statement->bindValue('currentTime', $currentTime, PDO::PARAM_STR);

        try {
            $statement->execute();
        } catch (Exception $e) {
            throw new AppException(__CLASS__, $e->getMessage());
        }
    }

    /**
     * Выполняет транзакцию по обновлению пароля пользователя и удалению токена сброса пароля.
     *
     * @param int $id Идентификатор пользователя.
     * @param string $passwordEncrypted Зашифрованный пароль пользователя.
     * @return array<string, string> Возвращает массив с ключом 'status' и значением 'ok' в случае успешного выполнения.
     * @throws AppException В случае ошибки выполнения транзакции выбрасывается исключение AppException.
     */
    public static function transactionUpdatePasswordUserAndDeleteResetPassword(int $id, string $passwordEncrypted): array
    {
        $connection = parent::$connection;
        $updateUser = $connection->prepare("UPDATE user SET passwordEncrypted = :passwordEncrypted WHERE id = :id");
        $deleteResetPassword = $connection->prepare("DELETE FROM " . self::DB_NAME . " WHERE userId = :userId");

        $connection->beginTransaction();

        try {
            $updateUser->execute(['passwordEncrypted' => $passwordEncrypted, 'id' => $id]);
            $deleteResetPassword->execute(['userId' => $id]);

            if ($updateUser->rowCount() === 0 || $deleteResetPassword->rowCount() === 0) {
                $connection->rollBack();

                $textError = 'Не удалось обновить пользователя или удалить токен сброса пароля';

                return Helper::showError($textError);
            }

            $connection->commit();

            return ['status' => 'ok'];
        } catch (Exception $e) {
            $connection->rollBack();

            throw new AppException(__CLASS__, $e->getMessage());
        }
    }
}
