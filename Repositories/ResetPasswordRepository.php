<?php

namespace Repositories;

use Core\Config;
use Core\Db;
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

        $statement->execute();
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
        $statement->execute();
    }
}
