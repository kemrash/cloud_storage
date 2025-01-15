<?php

namespace Core;

use Exception;
use PDO;

class Db
{
    private static ?Db $instance = null;
    protected static PDO $connection;

    private function __construct()
    {
        self::$connection = new PDO('mysql:host=localhost;dbname=cloud_storage;charset=utf8', 'root');
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new Exception("Нельзя восстановить экземпляр");
    }

    public static function getConnection()
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public static function findBy() {}

    public static function findOneBy() {}

    public static function findAll() {}

    public static function find() {}
}
