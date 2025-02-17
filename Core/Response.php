<?php

namespace Core;

class Response
{
    private string $data;
    private string $header;
    private int $statusCode;

    public function __construct(string $data, int $statusCode = 200, string $header = '')
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->header = $header;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
