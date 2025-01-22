<?php

namespace Core;

use DateTime;

class ErrorApp
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
}
