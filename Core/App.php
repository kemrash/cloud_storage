<?php

namespace Core;

class App
{
    private const FOLDERS = ['Repositories', 'Services', 'Models'];
    private static array $data = [];

    public static function getService(string $loverFirsLatterServiceName): mixed
    {
        if (self::issetClass($loverFirsLatterServiceName)) {
            return self::$data[$loverFirsLatterServiceName];
        }

        $className = ucfirst($loverFirsLatterServiceName);

        foreach (self::FOLDERS as $folder) {
            if (class_exists($folder . '\\' . $className)) {
                self::$data[$loverFirsLatterServiceName] = $folder === 'Repositories' ?
                    ($folder . '\\' . $className) :
                    self::$data[$loverFirsLatterServiceName] = new ($folder . '\\' . $className)();
            }
        }

        if (!isset(self::$data[$loverFirsLatterServiceName])) {
            throw new AppException(__CLASS__, 'Не найден сервис или репозиторий');
        }

        return self::$data[$loverFirsLatterServiceName];
    }

    public static function setService(string $name, mixed $service): void
    {
        if (isset(self::$data[$name])) {
            throw new AppException(__CLASS__, 'Такое название уже зарегистрировано');
        }

        self::$data[$name] = $service;
    }

    public static function issetClass(string $name): bool
    {
        return isset(self::$data[$name]);
    }
}
