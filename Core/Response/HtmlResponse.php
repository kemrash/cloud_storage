<?php

namespace Core\Response;

class HtmlResponse extends Response
{

    public function send(): void
    {
        echo $this->data;
    }
}
