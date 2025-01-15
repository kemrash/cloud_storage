<?php

namespace Core\Response;

abstract class Response
{
    protected string $data;
    protected int $statusCode;

    public function __construct(string $data, int $statusCode)
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
    }

    public function send(): void {}
}
