<?php

namespace Models;

use Core\App;
use Core\Config;

class ResetPassword
{
    private ?int $id;
    private int $userId;
    private string $hashedToken;
    private string $expiresAt;
    private string $createdAt;

    /**
     * Конструктор класса ResetPassword.
     *
     * @param int $userId Идентификатор пользователя.
     * @param string $hashedToken Хэшированный токен для сброса пароля.
     * @param string $expiresAt Дата и время истечения срока действия токена.
     * @param string $createdAt Дата и время создания записи.
     * @param int|null $id (Необязательный) Идентификатор записи.
     */
    public function __construct(int $userId, string $hashedToken, string $expiresAt, string $createdAt, ?int $id = null)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->hashedToken = $hashedToken;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
    }

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
    public function sendEmail(string $email, string $url, string $token): void
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

        App::getService('mail')->sendEmail($email, $title, $message);
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
}
