<?php

namespace Core\Response;

use Core\Response\Response;

class JsonResponse extends Response
{
    public function send(): void
    {
        header('Content-Type: application/json');
        http_response_code($this->statusCode);
        echo $this->data;
    }
}
