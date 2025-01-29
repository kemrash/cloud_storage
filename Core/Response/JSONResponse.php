<?php

namespace Core\Response;

use Core\Response;

class JSONResponse extends Response
{
    public function __construct(mixed $data = ['status' => 'ok'], int $statusCode = 200, string $header = '')
    {
        parent::__construct('json', json_encode($data), $statusCode, $header);
    }
}
