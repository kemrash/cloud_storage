<?php

namespace Traits;

use Core\Helper;
use Core\Response;
use Core\Response\AccessDeniedResponse;
use Core\Response\JSONResponse;

trait UserTrait
{
    private function checkUserAuthorization(): ?Response
    {
        if (!isset($_SESSION['id'])) {
            return new AccessDeniedResponse();
        }

        return null;
    }
}
