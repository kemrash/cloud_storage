<?php

namespace Core\Response;

use Core\Response;

class PageNotFoundResponse extends Response
{
    public function __construct()
    {
        parent::__construct('Страница не найдена', 404);
    }

    public function getData(): string
    {
        return $this->renderErrorResponse();
    }
}
