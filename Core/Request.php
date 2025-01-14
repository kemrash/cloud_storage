<?php

namespace Core;

class Request
{
    private string $uri;
    private string $method;
    private array $postData;
    private array $getData;

    public function __construct(string $uri, string $method, array $postData = [], array $getData = [])
    {
        $this->uri = $uri;
        $this->method = $method;
        $this->postData = $postData;
        $this->getData = $getData;
    }

    public function getData(): array
    {
        return array_merge($this->postData, $this->getData);
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
