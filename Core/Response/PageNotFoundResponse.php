<?php

namespace Core\Response;

use Core\Response;

class PageNotFoundResponse extends Response
{
    /**
     * Конструктор класса PageNotFoundResponse.
     * Устанавливает сообщение об ошибке "Страница не найдена" и код состояния 404.
     */
    public function __construct()
    {
        parent::__construct('Страница не найдена', 404);
    }

    /**
     * Возвращает данные в виде строки.
     *
     * @return string Строка с данными об ошибке.
     */
    public function getData(): string
    {
        return $this->renderErrorResponse();
    }
}
