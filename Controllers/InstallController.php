<?php

namespace Controllers;

use Core\App;
use Core\Request;
use Core\Response;
use Core\Response\PageNotFoundResponse;

class InstallController
{
    /**
     * Выполняет установку приложения.
     *
     * @param Request $request Объект запроса, содержащий данные для установки.
     * 
     * @return Response Возвращает объект ответа, который может быть либо PageNotFoundResponse, если файл конфигурации уже существует,
     * либо результат выполнения метода install сервиса установки.
     */
    public function install(Request $request): Response
    {
        if (file_exists('./config.php')) {
            return new PageNotFoundResponse();
        }

        return App::getService('installService')->install($request->getData()['POST']);
    }
}
