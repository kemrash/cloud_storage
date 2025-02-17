<?php

namespace Core\Response;

use Core\Response;

class ServerErrorResponse extends Response
{
    public function __construct()
    {
        parent::__construct('К сожалению произошла ошибка сервера, попробуйте позже', 500);
    }

    public function getData(): string
    {
        return $this->renderErrorResponse();
    }
}
