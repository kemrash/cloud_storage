<?php

namespace Core;

class App
{
    private const FOLDERS = ['Repositories', 'Services', 'Models'];
    private static array $data = [];

    /**
     * Возвращает экземпляр сервиса или репозитория по его имени.
     *
     * @param string $loverFirsLatterServiceName Имя сервиса с первой строчной буквой.
     * @return mixed Экземпляр сервиса или репозитория.
     * @throws AppException Если сервис или репозиторий не найден.
     */
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

    /**
     * Регистрирует новый сервис в приложении.
     *
     * @param string $name Название сервиса.
     * @param mixed $service Экземпляр сервиса.
     * 
     * @throws AppException Если сервис с таким названием уже зарегистрирован.
     * 
     * @return void
     */
    public static function setService(string $name, mixed $service): void
    {
        if (isset(self::$data[$name])) {
            throw new AppException(__CLASS__, 'Такое название уже зарегистрировано');
        }

        self::$data[$name] = $service;
    }

    /**
     * Проверяет, существует ли класс с заданным именем в массиве данных.
     *
     * @param string $name Имя класса для проверки.
     * @return bool Возвращает true, если класс существует, иначе false.
     */
    public static function issetClass(string $name): bool
    {
        return isset(self::$data[$name]);
    }
}
