<?php

namespace Traits;

use Core\Response;
use Core\Response\PageNotFoundResponse;

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
            return new PageNotFoundResponse();
        }

        return null;
    }
}
