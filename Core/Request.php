<?php

namespace Core;

class Request
{
    private string $uri;
    private string $method;

    public function __construct(string $uri, string $method)
    {
        $this->uri = $uri;
        $this->method = $method;
    }

    public function getData() {}

    public function getRoute(): string
    {
        return $this->uri;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}
