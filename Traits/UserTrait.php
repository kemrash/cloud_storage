<?php

namespace traits;

use Core\Response;
use Core\Response\AccessDeniedResponse;

trait UserTrait
{
    /**
     * Проверяет авторизацию пользователя.
     *
     * Если пользователь не авторизован (отсутствует идентификатор в сессии), 
     * возвращает объект AccessDeniedResponse. В противном случае возвращает null.
     *
     * @return ?Response Объект AccessDeniedResponse или null, если пользователь авторизован.
     */
    private function checkUserAuthorization(): ?Response
    {
        if (!isset($_SESSION['id'])) {
            return new AccessDeniedResponse();
        }

        return null;
    }
}
