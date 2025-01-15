<?php

namespace Core;

use Core\Response;

class JSONResponse extends Response
{
    public function setHeaders(string $header): void
    {
        $this->header = $header;
    }

    public function setData(string $data): void
    {
        $this->date = $data;
    }

    public function send(): void
    {
        header($this->header);
        echo $this->date;
    }
}
