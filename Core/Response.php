<?php

namespace Core;

class Response
{
    private string $type;
    private string $data;
    private string $header;
    private int $statusCode;

    public function __construct(string $type, string $data, int $statusCode = 200, string $header = '')
    {
        $this->type = $type;
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->header = $header;
    }

    public function getType()
    {
        return $this->type;
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
