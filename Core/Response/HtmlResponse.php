<?php

namespace Core\Response;

class HtmlResponse extends Response
{

    public function send(): void
    {
        http_response_code($this->statusCode);
        echo $this->data;
    }
}
