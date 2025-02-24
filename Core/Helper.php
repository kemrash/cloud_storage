<?php

namespace Core;

use DateTime;
use Throwable;

class Helper
{
    /**
     * Записывает сообщение в лог-файл.
     *
     * @param string $text Текст сообщения для записи в лог.
     *
     * @return void
     */
    public static function writeLog(string $text): void
    {
        file_put_contents('error.log', (new DateTime())->format('Y-m-d H:i:s') . ' ' . $text . PHP_EOL, FILE_APPEND);
    }

    /**
     * Возвращает массив с информацией об ошибке.
     *
     * @param string $text Текст ошибки. По умолчанию пустая строка.
     * @return array{status: string, data?: string} Массив с ключом 'status' и опциональным ключом 'data', содержащим текст ошибки.
     */
    public static function showError(string $text = ''): array
    {
        $data = $text === '' ? ['status' => 'error'] : ['status' => 'error', 'data' => trim($text)];

        return $data;
    }

    /**
     * Обработчик исключений.
     *
     * @param Throwable $e Исключение, которое нужно обработать.
     *
     * @return void
     */
    public static function exceptionHandler(Throwable $e): void
    {
        Helper::writeLog($e->getMessage());

        $response = new Response('renderError', 'К сожалению произошла ошибка сервера, попробуйте позже', 500);

        http_response_code($response->getStatusCode());
        echo $response->getData();
    }
}
