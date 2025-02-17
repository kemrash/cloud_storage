<?php

namespace Core\Response;

use Core\Response;

class AccessDeniedResponse extends Response
{
    public function __construct()
    {
        parent::__construct('Доступ запрещен', 403);
    }
}
