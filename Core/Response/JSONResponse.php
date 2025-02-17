<?php

namespace Core\Response;

use Core\Response;

class JSONResponse extends Response
{
    public function __construct(mixed $data = ['status' => 'ok'], int $statusCode = 200)
    {
        parent::__construct(json_encode($data), $statusCode, 'Content-Type: application/json');
    }
}
