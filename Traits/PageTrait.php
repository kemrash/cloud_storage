<?php

namespace Traits;

use Core\Response;

trait PageTrait
{
    private function checkNotFoundPage(mixed $id): ?Response
    {
        if (!isset($id) || !ctype_digit($id)) {
            return new Response('html', 'Страница не найдена', 404);
        }

        return null;
    }
}
