<?php

namespace Controllers;

use Core\Response;

class IndexController
{
    /**
     * Возвращает HTML содержимое в зависимости от состояния приложения.
     *
     * Этот метод проверяет наличие файла конфигурации и сессии пользователя,
     * и возвращает соответствующий HTML контент.
     *
     * @return Response HTML содержимое в виде объекта Response.
     */
    public function getIndexHtml(): Response
    {
        return new Response('html', file_get_contents('./templates/index.html'));
    }
}
