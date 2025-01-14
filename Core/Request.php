<?php

namespace Core;

class Request
{
    private static string $uri;
    private static string $method;
    private static array $postData;
    private static array $getData;

    public function __construct(string $uri, string $method, array $postData = [], array $getData = [])
    {
        self::$uri = $uri;
        self::$method = $method;
        self::$postData = $postData;
        self::$getData = $getData;
    }

    public static function getData(): array
    {
        return array_merge(self::$postData, self::$getData);
    }

    public static function getRoute(): string
    {
        return parse_url(self::$uri, PHP_URL_PATH) ?? '/';
    }

    public static function getMethod(): string
    {
        return self::$method;
    }
}
