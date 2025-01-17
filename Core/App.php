<?php

namespace Core;

class App
{
    private static array $data = [];

    public static function getService(string $loverFirsLatterServiceName): mixed
    {
        if (isset(self::$data[$loverFirsLatterServiceName])) {
            return self::$data[$loverFirsLatterServiceName];
        }

        $className = ucfirst($loverFirsLatterServiceName);

        if (class_exists('Repositories\\' . $className)) {
            self::$data[$loverFirsLatterServiceName] = ('Repositories\\' . $className);
        }

        if (class_exists('Services\\' . $className) || class_exists('Models\\' . $className)) {
            self::$data[$loverFirsLatterServiceName] = new ('Services\\' . $className)();
        }

        if (!isset(self::$data[$loverFirsLatterServiceName])) {
            http_response_code(500);
            die('Не найден сервис или репозиторий');
        }

        return self::$data[$loverFirsLatterServiceName];
    }
}
