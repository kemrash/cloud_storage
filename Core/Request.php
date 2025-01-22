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
