<?php

namespace Core;

use Exception;

class AppException extends Exception
{
    /**
     * Конструктор класса AppException.
     *
     * @param string $className Имя класса, в котором возникло исключение.
     * @param string $text Текст сообщения об ошибке.
     */
    public function __construct(string $className, string $text)
    {
        parent::__construct("{$className}: {$text}");
    }
}
