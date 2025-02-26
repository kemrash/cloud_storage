<?php

namespace traits;

use Core\Response;

trait StatusResponseTrait
{
    /**
     * Метод для возврата ответа о запрете доступа.
     *
     * @return Response Ответ с информацией о запрете доступа.
     */
    private function accessForbidden(): Response
    {
        return new Response('renderError', 'Доступ запрещен', 403);
    }

    /**
     * Возвращает ответ с ошибкой "Страница не найдена".
     *
     * @return Response Ответ с ошибкой "Страница не найдена".
     */
    private function pageNotFound(): Response
    {
        return new Response('renderError', 'Страница не найдена', 404);
    }
}
