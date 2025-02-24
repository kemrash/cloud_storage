<?php

namespace traits;

use Core\Response;

trait PageTrait
{
    /**
     * Проверяет, существует ли страница с указанным идентификатором.
     *
     * @param mixed $id Идентификатор страницы.
     * @return ?Response Возвращает объект PageNotFoundResponse, если страница не найдена, иначе null.
     */
    private function checkNotFoundPage(mixed $id): ?Response
    {
        if (!isset($id) || !ctype_digit($id)) {
            return new Response('renderError', 'Доступ запрещен', 403);
        }

        return null;
    }
}
