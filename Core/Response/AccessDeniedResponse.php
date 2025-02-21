<?php

namespace Core\Response;

use Core\Response;

class AccessDeniedResponse extends Response
{
    /**
     * Конструктор класса AccessDeniedResponse.
     * Устанавливает сообщение об ошибке "Доступ запрещен" и код состояния 403.
     */
    public function __construct()
    {
        parent::__construct('Доступ запрещен', 403);
    }

    /**
     * Возвращает данные в виде строки.
     *
     * @return string Строка с данными, представляющими собой ответ об ошибке.
     */
    public function getData(): string
    {
        return $this->renderErrorResponse();
    }
}
