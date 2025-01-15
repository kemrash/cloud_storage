<?php

namespace Core;

class Response
{
    protected string $header;
    protected string $date;

    public function setHeaders(string $header): void {}

    public function setData(string $data): void
    {
        $this->date = $data;
    }

    public function send(): void
    {
        echo $this->date;
    }
}
