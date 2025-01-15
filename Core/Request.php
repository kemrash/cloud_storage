<?php

namespace Core;

class Request
{
    private string $uri;
    private string $method;
    private array $postData;
    private array $getData;
    private array $putData;

    public function __construct(string $uri, string $method, array $postData = [], array $getData = [], array $putData = [])
    {
        $this->uri = $uri;
        $this->method = $method;
        $this->postData = $postData;
        $this->getData = $getData;
        $this->putData = $putData;
    }

    public function getData(): array
    {
        return array_merge($this->postData, $this->getData, $this->putData);
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
