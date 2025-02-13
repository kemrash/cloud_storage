<?php

namespace Controllers;

use Core\App;
use Core\Request;
use Core\Response;

class InstallController
{
    public function install(Request $request): Response
    {
        if (file_exists('./config.php')) {
            return new Response('html', 'Страница не найдена', 404);
        }

        return App::getService('installService')->install($request->getData()['POST']);
    }
}
