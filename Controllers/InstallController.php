<?php

namespace Controllers;

use Core\Install;
use Core\Request;
use Core\Response;
use traits\StatusResponseTrait;

class InstallController
{
    use StatusResponseTrait;

    /**
     * Устанавливает приложение, если файл конфигурации не существует.
     *
     * @param Request $request Объект запроса, содержащий данные для установки.
     * @return Response Ответ с результатом установки.
     *
     * Метод проверяет наличие файла конфигурации. Если файл существует, возвращается страница с ошибкой 404.
     * Если файла нет, создается объект установки с данными из POST-запроса, выполняется установка и возвращается
     * ответ с результатом. В случае ошибки установки возвращается ответ с кодом 400 и данными об ошибке.
     */
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
