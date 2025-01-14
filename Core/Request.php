<?php

namespace Core;

class Request
{
    private static string $uri;
    private static string $method;

    public function __construct(string $uri, string $method)
    {
        self::$uri = $uri;
        self::$method = $method;
    }

    public function getData() {}

    public static function getRoute(): string
    {
        return self::$uri;
    }

    public static function getMethod(): string
    {
        return self::$method;
    }
}
