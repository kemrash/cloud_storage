<?php

namespace Models;

use DateTime;

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

    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
    }
}
