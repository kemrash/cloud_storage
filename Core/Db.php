<?php

namespace Core;

use PDO;
use PDOException;
use Core\Config;

class Db
{
    private static array $allowedDatabases;
    private static ?Db $instance = null;
    protected static PDO $connection;

    private function __construct()
    {
        $dbConnection = Config::getConfig('database');
        $textConnection = 'mysql:host=' . $dbConnection['host'] . ';dbname=' . $dbConnection['name'] . ';charset=' . $dbConnection['charset'];

        self::$connection = new PDO($textConnection, $dbConnection['user'], $dbConnection['password']);
        self::$allowedDatabases = Config::getConfig('database.dbNames');
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new AppException(__CLASS__, "Нельзя восстановить экземпляр");
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
        self::validateDatabaseName($dbName);

        $safeColumns = array_intersect($allowedColumns, $columns);

        if (empty($safeColumns)) {
            throw new AppException(__CLASS__, "Нет допустимых столбцов для выбора.");
        }

        $columnList = implode(', ', array_map(fn($col) => "`$col`", $safeColumns));

        $sql = "SELECT {$columnList} FROM {$dbName}";

        $statement = self::$connection->prepare($sql);

        try {
            $statement->execute();

            return $statement->fetchAll();
        } catch (PDOException $e) {
            throw new AppException(__CLASS__, $e->getMessage());
        }
    }

    public static function findOneBy(string $dbName, array $params, array $allowedColumns): ?array
    {
        self::validateDatabaseName($dbName);

        $data = self::buildWhereClauseAndBindings($params, $allowedColumns);

        $sql = "SELECT * FROM {$dbName} WHERE {$data['whereClause']} LIMIT 1";

        $statement = self::$connection->prepare($sql);

        try {
            $statement->execute($data['bindings']);

            $result = $statement->fetch();

            return $result ? $result : null;
        } catch (PDOException $e) {
            throw new AppException(__CLASS__, $e->getMessage());
        }
    }

    public static function insert(string $dbName, array $params, array $allowedColumns): void
    {
        self::validateDatabaseName($dbName);

        $conditions = [];
        $bindings = [];

        foreach ($allowedColumns as $allowedColumn) {
            if (!isset($params[$allowedColumn])) {
                $conditions[] = "null";
            } else {
                $conditions[] = ":{$allowedColumn}";
                $bindings[$allowedColumn] = $params[$allowedColumn];
            }
        }

        $preBinding = implode(', ', $conditions);
        $dbColumns = implode(', ', $allowedColumns);

        $sql = "INSERT INTO " . $dbName . " ({$dbColumns}) VALUES ({$preBinding})";

        $statement = self::$connection->prepare($sql);
        $statement->execute($bindings);
    }

    public static function updateOneBy(string $dbName, array $paramsSet, array $paramsWhere, array $allowedColumns): array
    {
        self::validateDatabaseName($dbName);

        $statementSettings = ['settingSet', 'settingWhere'];

        foreach ($statementSettings as &$setting) {
            $params = [];

            if ($setting === 'settingSet') {
                $params = $paramsSet;
                $separator = ', ';
            }

            if ($setting === 'settingWhere') {
                $params = $paramsWhere;
                $separator = ' AND ';
            }

            $setting = [];
            $setting['conditions'] = [];
            $setting['bindings'] = [];


            foreach ($params as $key => $value) {
                if (!in_array($key, $allowedColumns, true)) {
                    $textError = "Недопустимая колонка: $key";

                    Helper::writeLog(get_class() . ': ' . $textError);

                    return Helper::showError($textError);
                }

                $setting['conditions'][] = "{$key} = :{$key}";
                $setting['bindings'][$key] = $value;
            }

            $setting['whereClause'] = implode($separator, $setting['conditions']);
        }

        $sql = "UPDATE {$dbName} SET {$statementSettings[0]['whereClause']} WHERE {$statementSettings[1]['whereClause']}";

        $statement = self::$connection->prepare($sql);

        try {
            $statement->execute(array_merge($statementSettings[0]['bindings'], $statementSettings[1]['bindings']));

            return ['status' => 'ok'];
        } catch (PDOException $e) {
            $errorCode = $e->getCode();

            if ($errorCode === '23000') {
                $error = Helper::showError();
                $error['code'] = $errorCode;

                return $error;
            }

            throw new AppException(__CLASS__, $e->getMessage());
        }
    }

    public static function deleteOneBy(string $dbName, array $paramsWhere, array $allowedColumns)
    {
        self::validateDatabaseName($dbName);

        $data = self::buildWhereClauseAndBindings($paramsWhere, $allowedColumns);

        $sql = "DELETE FROM {$dbName} WHERE {$data['whereClause']}";

        $statement = self::$connection->prepare($sql);

        try {
            $statement->execute($data['bindings']);

            $result = $statement->fetch();

            return $result ? $result : null;
        } catch (PDOException $e) {
            throw new AppException(__CLASS__, $e->getMessage());
        }
    }

    private static function buildWhereClauseAndBindings(array $params, array $allowedColumns, string $separator = ' AND '): array
    {
        $conditions = [];
        $bindings = [];

        foreach ($params as $key => $value) {
            if (!in_array($key, $allowedColumns, true)) {
                throw new AppException(__CLASS__, "Недопустимая колонка: $key");
            }

            $conditions[] = "{$key} = :{$key}";
            $bindings[$key] = $value;
        }

        $whereClause = implode($separator, $conditions);

        return ['whereClause' => $whereClause, 'bindings' => $bindings];
    }

    private static function validateDatabaseName(string $dbName): void
    {
        if (!in_array($dbName, self::$allowedDatabases, true)) {
            throw new AppException(__CLASS__, 'Недопустимое имя базы данных');
        }
    }

    // self::$connection->prepare("DELETE FROM " . $dbName . " WHERE userId = :userId");

    // public static function findAll() {}

    // public static function find() {}
}
