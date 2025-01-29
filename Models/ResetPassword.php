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

    public function __construct(int $userId, string $hashedToken, string $expiresAt, string $createdAt, ?int $id = null)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->hashedToken = $hashedToken;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
    }

    public function __get($name): null|int|string
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
    }

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

    public function isValidToken(string $token): bool
    {
        return password_verify($token, $this->hashedToken);
    }
}
