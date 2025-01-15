<?php

namespace Core;

use Core\Response\HtmlResponse;
use Core\Response\JsonResponse;
use Core\Response\Response;
use Exception;

class ResponseFactory
{
    public static function createResponse(string $type, string $data, int $statusCode = 200): Response
    {
        switch ($type) {
            case 'json':
                return new JsonResponse($data, $statusCode);
                break;

            case 'html':
                return new HtmlResponse($data, $statusCode);
                break;

            default:
                throw new Exception("Неизвестный тип: $type");
        }
    }
}
