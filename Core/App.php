<?php

namespace Core;

use Exception;
use Traits\StatusResponseTrait;

class App
{
    use StatusResponseTrait;

    private const FOLDERS = ['Models', 'Core'];
    private static array $data = [];

    /**
     * Возвращает экземпляр сервиса или репозитория по его имени.
     *
     * @param string $loverFirsLatterServiceName Имя сервиса с первой строчной буквой.
     * @return mixed Экземпляр сервиса или репозитория.
     * @throws Exception Если сервис или репозиторий не найден.
     */
    public static function getService(string $loverFirsLatterServiceName): mixed
    {
        if (self::issetClass($loverFirsLatterServiceName)) {
            return self::$data[$loverFirsLatterServiceName];
        }

        $className = ucfirst($loverFirsLatterServiceName);

        foreach (self::FOLDERS as $folder) {
            if (class_exists($folder . '\\' . $className)) {
                self::$data[$loverFirsLatterServiceName] = self::$data[$loverFirsLatterServiceName] = new ($folder . '\\' . $className)();
            }
        }

        if (!isset(self::$data[$loverFirsLatterServiceName])) {
            throw new Exception(__CLASS__ . ': ' . 'Не найден сервис или репозиторий');
        }

        return self::$data[$loverFirsLatterServiceName];
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

    /**
     * Запускает основное приложение.
     *
     * Устанавливает обработчик исключений, создает объект запроса, проверяет наличие конфигурационного файла,
     * устанавливает соединение с базой данных, запускает сессию, обрабатывает запрос с помощью маршрутизатора,
     * устанавливает код ответа HTTP, заголовки и выводит данные ответа.
     *
     * @return void
     */
    public static function run(): void
    {
        set_exception_handler([Helper::class, 'exceptionHandler']);

        $request = new Request();

        if (file_exists('./config.php')) {
            Db::getConnection();
        }

        $session = new Session();
        $session->startSession();

        $router = new Router();
        $response = $router->processRequest($request);

        if ($response === null) {
            $response = self::pageNotFound();
        }

        http_response_code($response->getStatusCode());
        header($response->getHeader());
        echo $response->getData();
    }
}
