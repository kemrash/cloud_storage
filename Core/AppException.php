<?php

namespace Core;

use Exception;

class AppException extends Exception
{
    public function __construct(string $className, string $text)
    {
        parent::__construct("{$className}: {$text}");
    }

    public function log(): void
    {
        Helper::writeLog($this->getMessage());
    }
}
