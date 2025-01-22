<?php

namespace Controllers;

use Core\App;
use Core\Response;

class IndexController
{
    public function getIndexHtml()
    {
        App::getService('session')->startSession();

        if (isset($_SESSION['id'])) {
            echo $_SESSION['id'];
        }

        return new Response('html', file_get_contents('./Templates/index.html'));
    }
}
