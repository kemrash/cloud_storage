<?php

namespace Core;

class Config
{
    private static array $config;

    /**
     * Получает значение конфигурации по заданному ключу.
     *
     * @param string|null $key Ключ конфигурации в формате "ключ1.ключ2.ключ3". Если null, возвращает весь массив конфигурации.
     * @param mixed $default Значение по умолчанию, возвращаемое, если ключ не найден. Если не указано, выбрасывается исключение.
     * @return mixed Значение конфигурации по заданному ключу или значение по умолчанию.
     * @throws AppException Если ключ не найден и значение по умолчанию не указано.
     */
    public static function getConfig(?string $key = null, mixed $default = null): mixed
    {
        if (!isset(self::$config)) {
            self::$config = require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php';
        }

        if ($key === null) {
            return self::$config;
        }

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                if (func_num_args() === 2) {
                    return $default;
                }

                throw new AppException(__CLASS__, 'Несуществующий элемент конфигурации.');
            }

            $value = $value[$k];
        }

        return $value;
    }
}
