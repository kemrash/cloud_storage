<?php

namespace Core;

use Exception;

class Config
{
    private static array $config;

    public static function getConfig(?string $key = null, mixed $default = null): mixed
    {
        if (!isset(self::$config)) {
            self::$config = require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php';
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
