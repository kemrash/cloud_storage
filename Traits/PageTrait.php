<?php

namespace Traits;

use Core\Response;
use Core\Response\PageNotFoundResponse;

trait PageTrait
{
    private function checkNotFoundPage(mixed $id): ?Response
    {
        if (!isset($id) || !ctype_digit($id)) {
            return new PageNotFoundResponse();
        }

        return null;
    }
}
