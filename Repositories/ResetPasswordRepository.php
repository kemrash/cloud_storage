<?php

namespace Repositories;

use Core\Config;
use Core\Db;
use Core\Helper;
use Exception;
use Models\ResetPassword;
use PDO;

class ResetPasswordRepository extends Db
{
    const DB_NAME = 'reset_password';

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
            Helper::writeLog(self::class . ': ' . $e->getMessage());

            throw $e;
        }
    }

    public static function getResetPasswordBy(array $params): ?ResetPassword
    {
        $data = parent::findOneBy(self::DB_NAME, $params, Config::getConfig('database.dbColumns.reset_password'));

        if ($data === null) {
            return null;
        }

        return new ResetPassword($data['id'], $data['hashedToken'], $data['expiresAt'], $data['createdAt'], $data['id']);
    }

    public static function clearOldResetPassword(string $currentTime): void
    {
        $statement = parent::$connection->prepare('DELETE FROM ' . self::DB_NAME . ' WHERE expiresAt < :currentTime');
        $statement->bindValue('currentTime', $currentTime, PDO::PARAM_STR);

        try {
            $statement->execute();
        } catch (Exception $e) {
            Helper::writeLog(self::class . ': ' . $e->getMessage());

            throw $e;
        }
    }

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
            Helper::writeLog(self::class . ': ' . $e->getMessage());

            throw $e;
        }
    }
}
