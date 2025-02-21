<?php

namespace Core;

class Request
{
    private string $uri;
    private string $method;
    private array $data;

    /**
     * Конструктор класса Request.
     *
     * Инициализирует объект запроса, извлекая данные из глобальных массивов $_SERVER, $_POST, $_GET, $_FILES и php://input.
     *
     * @property string $uri URI запроса.
     * @property string $method Метод HTTP запроса.
     * @property array<string, mixed> $data Ассоциативный массив данных запроса, содержащий ключи 'POST', 'GET', 'PUT', 'FILES' и 'originUrl'.
     */
    public function __construct()
    {
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->data['POST'] = $_POST;
        $this->data['GET'] = $_GET;
        parse_str(file_get_contents('php://input'), $this->data['PUT']);
        $this->data['FILES'] = $_FILES;

        $scheme = isset($_SERVER['REQUEST_SCHEME']) && is_string($_SERVER['REQUEST_SCHEME'])
            ? $_SERVER['REQUEST_SCHEME']
            : 'http';

        $host = isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST'])
            ? $_SERVER['HTTP_HOST']
            : 'localhost';

        $this->data['originUrl'] = isset($_SERVER['HTTP_ORIGIN']) && is_string($_SERVER['HTTP_ORIGIN'])
            ? $_SERVER['HTTP_ORIGIN']
            : ($scheme . '://' . $host);
    }

    /**
     * Возвращает данные запроса.
     *
     * @return array<string, mixed> Ассоциативный массив данных запроса.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Возвращает маршрут из URI.
     *
     * @return string Маршрут из URI или '/' по умолчанию.
     */
    public function getRoute(): string
    {
        return parse_url($this->uri, PHP_URL_PATH) ?? '/';
    }

    /**
     * Возвращает HTTP-метод текущего запроса.
     *
     * @return string HTTP-метод запроса.
     */
    public function getMethod(): string
    {
        return $this->method;
    }
}
