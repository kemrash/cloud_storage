<?php

namespace Core\Response;

use Core\Response;

class ServerErrorResponse extends Response
{
    /**
     * Конструктор класса ServerErrorResponse.
     * 
     * Создает новый экземпляр ServerErrorResponse с сообщением об ошибке сервера и кодом состояния 500.
     */
    public function __construct()
    {
        parent::__construct('К сожалению произошла ошибка сервера, попробуйте позже', 500);
    }

    /**
     * Возвращает данные в виде строки.
     *
     * @return string Строка с данными об ошибке сервера.
     */
    public function getData(): string
    {
        return $this->renderErrorResponse();
    }
}
