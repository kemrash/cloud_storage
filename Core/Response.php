<?php

namespace Core;

class Response
{
    private string $type;
    private mixed $data;
    private string $header;
    private int $statusCode;

    /**
     * Конструктор класса Response.
     *
     * @param mixed $data Данные ответа.
     * @param int $statusCode Код статуса HTTP (по умолчанию 200).
     * @param string $header Заголовок ответа (по умолчанию пустая строка).
     */
    public function __construct(string $type = 'json', mixed $data = ['status' => 'ok'], int $statusCode = 200, string $header = '')
    {
        $this->type = $type;
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->header = $header;
    }

    /**
     * Возвращает данные в виде строки в зависимости от типа ответа.
     *
     * @return string Строка данных, закодированная в JSON, если тип 'json', 
     *                или результат вызова метода renderErrorResponse, если тип 'renderError', 
     *                или строковое представление данных в остальных случаях.
     */
    public function getData(): string
    {
        if ($this->type === 'json') {
            return json_encode($this->data);
        }

        if ($this->type === 'renderError') {
            return $this->renderErrorResponse();
        }

        return (string) $this->data;
    }

    /**
     * Возвращает заголовок ответа.
     *
     * Если заголовок пуст и тип ответа 'json', возвращает заголовок 'Content-Type: application/json'.
     *
     * @return string Заголовок ответа.
     */
    public function getHeader(): string
    {
        if ($this->header === '' && $this->type === 'json') {
            return 'Content-Type: application/json';
        }

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
        $render = new Render('error.html', ['code' => $this->statusCode, 'message' => (string) $this->data]);

        return $render->getRender();
    }
}
