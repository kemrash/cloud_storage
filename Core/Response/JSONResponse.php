<?php

namespace Core\Response;

use Core\Response;

class JSONResponse extends Response
{
    /**
     * Конструктор JSONResponse.
     *
     * @param mixed $data Данные для кодирования в JSON. По умолчанию ['status' => 'ok'].
     * @param int $statusCode Код состояния HTTP. По умолчанию 200.
     */
    public function __construct(mixed $data = ['status' => 'ok'], int $statusCode = 200)
    {
        parent::__construct(json_encode($data), $statusCode, 'Content-Type: application/json');
    }
}
