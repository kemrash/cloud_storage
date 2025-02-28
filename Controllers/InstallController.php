<?php

namespace Controllers;

use Core\Install;
use Core\Request;
use Core\Response;
use traits\StatusResponseTrait;

class InstallController
{
    use StatusResponseTrait;

    public function install(Request $request): Response
    {
        if (file_exists('./config.php')) {
            return $this->pageNotFound();
        }

        $install = new Install($request->getData()['POST']);
        $data = $install->run();

        if ($data['status'] !== 'ok') {
            return new Response('json', $data, 400);
        }

        return new Response();
    }
}
