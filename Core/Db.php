<?php

namespace Core;

use Exception;
use PDO;

class Db
{
    const ALLOWED_DATABASES = ['user'];
    private static ?Db $instance = null;
    private static PDO $connection;

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

    public static function findBy(string $dbName, array $columns, array $allowedColumns): array
    {
        if (!in_array($dbName, self::ALLOWED_DATABASES, true)) {
            throw new Exception("Недопустимое имя базы данных");
        }

        $safeColumns = array_intersect($allowedColumns, $columns);

        if (empty($safeColumns)) {
            throw new Exception('Нет допустимых столбцов для выбора.');
        }

        $columnList = implode(', ', array_map(fn($col) => "`$col`", $safeColumns));

        $sql = "SELECT {$columnList} FROM {$dbName}";

        $statement = self::$connection->prepare($sql);
        $statement->execute();

        return $statement->fetchAll();
    }

    public static function findOneBy(string $dbName, array $params, array $allowedColumns): ?array
    {
        if (!in_array($dbName, self::ALLOWED_DATABASES, true)) {
            throw new Exception("Недопустимое имя базы данных");
        }

        $conditions = [];
        $bindings = [];

        foreach ($params as $key => $value) {
            if (!in_array($key, $allowedColumns, true)) {
                throw new Exception("Недопустимая колонка: $key");
            }

            $conditions[] = "{$key} = :{$key}";
            $bindings[$key] = $value;
        }

        $whereClause = implode(' AND ', $conditions);

        $sql = "SELECT * FROM {$dbName} WHERE {$whereClause} LIMIT 1";

        $statement = self::$connection->prepare($sql);
        $statement->execute($bindings);

        $result = $statement->fetch();

        return $result ?: null;
    }

    // public static function findAll() {}

    // public static function find() {}
}
