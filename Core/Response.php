<?php

namespace Core;

class Response
{
    public function setData(string $data): void
    {
        echo $data;
    }

    public function setHeaders() {}
}
