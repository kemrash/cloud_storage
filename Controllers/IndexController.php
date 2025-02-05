<?php

namespace Controllers;

use Core\Response;

class IndexController
{
    public function getIndexHtml()
    {
        if (!isset($_SESSION['id'])) {
            return new Response('html', file_get_contents('./Templates/index.html'));
        }

        return new Response('html', file_get_contents('./Templates/form.html'));
    }
}
