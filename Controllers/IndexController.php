<?php

namespace Controllers;

use Core\Response;

class IndexController
{
    public function getIndexHtml()
    {
        return new Response('html', file_get_contents('./Templates/index.html'));
    }
}
