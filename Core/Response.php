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

    public function getData(): string
    {
        return $this->data;
    }

    public function getHeader(): string
    {
        return $this->header;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    protected function renderErrorResponse(): string
    {
        $render = new Render('error.html', ['code' => $this->statusCode, 'message' => $this->data]);

        return $render->getRender();
    }
}
