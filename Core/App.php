<?php

namespace Core;

use Core\Request;

class App
{
    private static array $data = [];
    private static array $folders = [];

    public function __construct(Request $request, array $folders)
    {
        self::$data['request'] = $request;
        self::$folders = $folders;
    }

    public static function getService(string $loverFirsLatterServiceName): mixed
    {
        if (isset(self::$data[$loverFirsLatterServiceName])) {
            return self::$data[$loverFirsLatterServiceName];
        }

        $className = ucfirst($loverFirsLatterServiceName);

        foreach (self::$folders as $folder) {
            if (class_exists($folder . '\\' . $className)) {
                self::$data[$loverFirsLatterServiceName] = new ($folder . '\\' . $className)();
                break;
            }
        }

        if (!isset(self::$data[$loverFirsLatterServiceName])) {
            http_response_code(500);
            die('Не найден сервис или репозиторий');
        }

        return self::$data[$loverFirsLatterServiceName];
    }
}
