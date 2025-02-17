<?php

namespace Core;

use Core\Response\ServerErrorResponse;
use DateTime;
use Throwable;

class Helper
{
    public static function writeLog(string $text): void
    {
        file_put_contents('error.log', (new DateTime())->format('Y-m-d H:i:s') . ' ' . $text . PHP_EOL, FILE_APPEND);
    }

    public static function showError(string $text = ''): array
    {
        $data = $text === '' ? ['status' => 'error'] : ['status' => 'error', 'data' => trim($text)];

        return $data;
    }

    public static function exceptionHandler(Throwable $e): void
    {
        Helper::writeLog($e->getMessage());

        $response = new ServerErrorResponse();

        http_response_code($response->getStatusCode());
        echo $response->getData();
    }
}
