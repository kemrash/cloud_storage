<?php

namespace Core;

class Request
{
    private string $uri;
    private string $method;
    private array $data;

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

    public function getData(): array
    {
        return $this->data;
    }

    public function getRoute(): string
    {
        return parse_url($this->uri, PHP_URL_PATH) ?? '/';
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}
