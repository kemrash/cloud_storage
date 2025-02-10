<?php

namespace Traits;

use Core\Helper;
use Core\Response;
use Core\Response\JSONResponse;

trait UserTrait
{
    private function checkUserAuthorization(): ?Response
    {
        if (!isset($_SESSION['id'])) {
            return new JSONResponse(Helper::showError('Доступ запрещен'), 403);
        }

        return null;
    }
}
