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
        // if (!file_exists('./config.php')) {
        //     return new Response(file_get_contents('./Templates/install.html'));
        // }

        // if (!isset($_SESSION['id'])) {
        //     return new Response(file_get_contents('./Templates/index.html'));
        // }

        // return new Response(file_get_contents('./Templates/file.html'));

        return new Response(file_get_contents('./Templates/index.html'));
    }
}
