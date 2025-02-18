<?php

namespace Core;

class Response
{
    private string $data;
    private string $header;
    private int $statusCode;

    /**
     * Конструктор класса Response.
     *
     * @param string $data Данные ответа.
     * @param int $statusCode Код статуса HTTP (по умолчанию 200).
     * @param string $header Заголовок ответа (по умолчанию пустая строка).
     */
    public function __construct(string $data, int $statusCode = 200, string $header = '')
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->header = $header;
    }

    /**
     * Возвращает данные в виде строки.
     *
     * @return string Данные в виде строки.
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * Возвращает заголовок ответа.
     *
     * @return string Заголовок ответа.
     */
    public function getHeader(): string
    {
        return $this->header;
    }

    /**
     * Возвращает статусный код ответа.
     *
     * @return int Статусный код ответа.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Генерирует HTML-ответ с ошибкой.
     *
     * @return string HTML-код страницы ошибки.
     */
    protected function renderErrorResponse(): string
    {
        $render = new Render('error.html', ['code' => $this->statusCode, 'message' => $this->data]);

        return $render->getRender();
    }
}
