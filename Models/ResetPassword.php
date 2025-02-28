<?php

namespace Models;

use Core\App;
use Core\Config;
use Core\Db;
use Core\Helper;
use Core\Mail;
use DateTime;
use Exception;
use PDO;

class ResetPassword
{
    private const DB_NAME = 'reset_password';
    private ?int $id;
    private int $userId;
    private string $hashedToken;
    private string $expiresAt;
    private string $createdAt;

    /**
     * Магический метод для получения значения свойства класса.
     *
     * @param string $name Имя свойства, значение которого нужно получить.
     * @return null|int|string Значение свойства, если оно существует, иначе null.
     */
    public function __get($name): null|int|string
    {
        if (isset($this->$name)) {
            return $this->$name;
        }

        return null;
    }

    /**
     * Подготавливает сброс пароля и отправляет письмо.
     *
     * @param string $email Электронная почта пользователя.
     * @param string $url URL для сброса пароля.
     * @param int $expiresInMinutes Время действия ссылки в минутах.
     * @return array|null Возвращает массив с ключом 'status' и значением 'ok' в случае успешной отправки, иначе null.
     */
    public function preparationAndSendEmail(string $email, string $url, int $expiresInMinutes): ?array
    {
        $dateTime = new DateTime();
        $createdAt = $dateTime->format(Config::getConfig('app.dateTimeFormat'));

        $this->clearOld($createdAt);

        $user = App::getService('user');

        if (!$user->get(['email' => $email])) {
            return null;
        }

        $resetPassword = $this->getBy(['userId' => $user->id]);

        if ($resetPassword !== null && DateTime::createFromFormat(Config::getConfig('app.dateTimeFormat'), $resetPassword['expiresAt']) >= $dateTime) {
            return null;
        }

        $expiresAt = $dateTime->modify("+{$expiresInMinutes} minutes")->format(Config::getConfig('app.dateTimeFormat'));

        $token = bin2hex(random_bytes(32));
        $hashedToken = password_hash($token, PASSWORD_DEFAULT);

        $this->id = null;
        $this->userId = $user->id;
        $this->hashedToken = $hashedToken;
        $this->expiresAt = $expiresAt;
        $this->createdAt = $createdAt;

        $this->created();
        $this->sendEmail($user->email, $url, $token);

        return ['status' => 'ok'];
    }

    /**
     * Сбрасывает пароль пользователя.
     *
     * @param int $id Идентификатор пользователя.
     * @param string $token Токен для сброса пароля.
     * @param string $password Новый пароль.
     * @return array<string, string>|null Возвращает массив с результатом операции или null, если токен недействителен.
     */
    public function resetPassword(int $id, string $token, string $password): ?array
    {
        $dateTime = new DateTime();
        $currentDate = $dateTime->format(Config::getConfig('app.dateTimeFormat'));

        $this->clearOld($currentDate);

        $resetPassword = $this->getBy(['userId' => $id]);

        if ($resetPassword === null || !$this->isValidToken($token)) {
            return null;
        }

        $passwordEncrypted = password_hash($password, PASSWORD_DEFAULT);

        $data = $this->transactionUpdatePasswordUserAndDeleteResetPassword($id, $passwordEncrypted);

        if ($data['status'] === 'error') {
            return $data;
        }

        return ['status' => 'ok'];
    }

    /**
     * Создает новую запись в базе данных для сброса пароля.
     *
     * Метод подготавливает SQL-запрос для вставки новой записи в таблицу сброса паролей.
     * Он связывает значения параметров с подготовленным запросом и выполняет его.
     * В случае ошибки выбрасывается исключение с сообщением об ошибке.
     *
     * @throws Exception Если выполнение запроса не удалось.
     *
     * @return void
     */
    private function created(): void
    {
        $statement = Db::$connection->prepare("
            INSERT INTO " . self::DB_NAME . " (userId, hashedToken, expiresAt, createdAt)
            VALUES (:userId, :hashedToken, :expiresAt, :createdAt)
        ");

        $statement->bindValue('userId', $this->userId, PDO::PARAM_INT);
        $statement->bindValue('hashedToken', $this->hashedToken, PDO::PARAM_STR);
        $statement->bindValue('expiresAt', $this->expiresAt, PDO::PARAM_STR);
        $statement->bindValue('createdAt', $this->createdAt, PDO::PARAM_STR);

        try {
            $statement->execute();
        } catch (Exception $e) {
            throw new Exception(__CLASS__ . ': ' . $e->getMessage());
        }
    }

    /**
     * Получает данные из базы данных по заданным параметрам.
     *
     * @param array<string, mixed> $params Ассоциативный массив параметров для поиска.
     * 
     * @return array<string, mixed>|null Возвращает ассоциативный массив данных, если запись найдена, или null, если запись не найдена.
     */
    private function getBy(array $params): ?array
    {
        $data = Db::findOneBy(self::DB_NAME, $params, Config::getConfig('database.dbColumns.reset_password'));

        if ($data === null) {
            return null;
        }

        $this->id = $data['id'];
        $this->userId = $data['userId'];
        $this->hashedToken = $data['hashedToken'];
        $this->expiresAt = $data['expiresAt'];
        $this->createdAt = $data['createdAt'];

        return $data;
    }

    /**
     * Удаляет устаревшие записи из базы данных.
     *
     * @param string $currentTime Текущее время в формате строки, используемое для сравнения с полем expiresAt.
     *
     * @throws Exception Если выполнение запроса завершилось неудачно, выбрасывается исключение с сообщением об ошибке.
     */
    private function clearOld(string $currentTime): void
    {
        $statement = Db::$connection->prepare('DELETE FROM ' . self::DB_NAME . ' WHERE expiresAt < :currentTime');
        $statement->bindValue('currentTime', $currentTime, PDO::PARAM_STR);

        try {
            $statement->execute();
        } catch (Exception $e) {
            throw new Exception(__CLASS__ . ': ' . $e->getMessage());
        }
    }

    /**
     * Отправляет электронное письмо для восстановления пароля.
     *
     * @param string $email Адрес электронной почты получателя.
     * @param string $url URL для восстановления пароля.
     * @param string $token Токен для восстановления пароля.
     *
     * @return void
     */
    private function sendEmail(string $text, string $url, string $token): void
    {
        $title = Config::getConfig('app.name') . ' - Восстановление пароля';
        $message =  'Вы, либо кто-то, попросил восстановить пароль для вашего аккаунта на сайте ' .
            Config::getConfig('app.name') .
            '. Вы можете восстановить пароль перейдя по ссылке: ' .
            $url .
            '?id=' .
            $this->userId .
            '&token=' .
            $token .
            '. Если вы не запрашивали восстановление пароля, проигнорируйте это письмо.';

        $email = new Mail();
        $email->sendEmail($text, $title, $message);
    }

    /**
     * Проверяет, является ли переданный токен действительным.
     *
     * @param string $token Токен для проверки.
     * @return bool Возвращает true, если токен действителен, иначе false.
     */
    public function isValidToken(string $token): bool
    {
        return password_verify($token, $this->hashedToken);
    }

    /**
     * Выполняет транзакцию по обновлению пароля пользователя и удалению записи сброса пароля.
     *
     * @param int $id Идентификатор пользователя.
     * @param string $passwordEncrypted Зашифрованный пароль пользователя.
     * @return array<string, string> Массив с результатом выполнения операции.
     * @throws Exception В случае ошибки выполнения транзакции.
     */
    private function transactionUpdatePasswordUserAndDeleteResetPassword(int $id, string $passwordEncrypted): array
    {
        $connection = Db::$connection;
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

            throw new Exception(__CLASS__ . ': ' . $e->getMessage());
        }
    }
}
