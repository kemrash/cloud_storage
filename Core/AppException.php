<?php

namespace Core;

use Exception;

class AppException extends Exception
{
    public function __construct(string $className, string $text)
    {
        parent::__construct("{$className}: {$text}");
    }
}
