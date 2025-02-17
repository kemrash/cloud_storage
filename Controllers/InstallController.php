<?php

namespace Controllers;

use Core\App;
use Core\Request;
use Core\Response;
use Core\Response\PageNotFoundResponse;

class InstallController
{
    public function install(Request $request): Response
    {
        if (file_exists('./config.php')) {
            return new PageNotFoundResponse();
        }

        return App::getService('installService')->install($request->getData()['POST']);
    }
}
