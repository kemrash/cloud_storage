<?php

namespace Core;

use Exception;
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
        $textError = "Нельзя восстановить экземпляр";

        Helper::writeLog($textError);

        throw new Exception($textError);
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
        if (!in_array($dbName, self::$allowedDatabases, true)) {
            Helper::writeLog("Недопустимое имя базы данных");

            return [];
        }

        $safeColumns = array_intersect($allowedColumns, $columns);

        if (empty($safeColumns)) {
            Helper::writeLog("Нет допустимых столбцов для выбора.");

            return [];
        }

        $columnList = implode(', ', array_map(fn($col) => "`$col`", $safeColumns));

        $sql = "SELECT {$columnList} FROM {$dbName}";

        try {
            $statement = self::$connection->prepare($sql);
            $statement->execute();

            return $statement->fetchAll();
        } catch (PDOException $e) {
            Helper::writeLog($e->getMessage());

            return [];
        }
    }

    public static function findOneBy(string $dbName, array $params, array $allowedColumns): ?array
    {
        if (!in_array($dbName, self::$allowedDatabases, true)) {
            $textError = "Недопустимое имя базы данных";

            Helper::writeLog($textError);

            throw new Exception($textError);
        }

        $conditions = [];
        $bindings = [];

        foreach ($params as $key => $value) {
            if (!in_array($key, $allowedColumns, true)) {
                $textError = "Недопустимая колонка: $key";

                Helper::writeLog($textError);

                throw new Exception($textError);
            }

            $conditions[] = "{$key} = :{$key}";
            $bindings[$key] = $value;
        }

        $whereClause = implode(' AND ', $conditions);

        $sql = "SELECT * FROM {$dbName} WHERE {$whereClause} LIMIT 1";

        try {
            $statement = self::$connection->prepare($sql);
            $statement->execute($bindings);

            $result = $statement->fetch();

            return $result ? $result : null;
        } catch (PDOException $e) {
            $textError = $e->getMessage();
            Helper::writeLog(self::class . ': ' .  $textError);

            throw new Exception($e->getMessage());
        }
    }

    public static function updateOneBy(string $dbName, array $paramsSet, array $paramsWhere, array $allowedColumns): array
    {
        if (!in_array($dbName, self::$allowedDatabases, true)) {
            $textError = "Недопустимое имя базы данных";

            Helper::writeLog(get_class() . ': ' . $textError);

            return Helper::showError($textError);
        }

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

        try {
            $statement = self::$connection->prepare($sql);
            $statement->execute(array_merge($statementSettings[0]['bindings'], $statementSettings[1]['bindings']));

            return ['status' => 'ok'];
        } catch (PDOException $e) {
            $errorCode = $e->getCode();

            if ($errorCode === '23000') {
                $error = Helper::showError();
                $error['code'] = $errorCode;

                return $error;
            }

            Helper::writeLog(get_class() . ': ' . $e->getMessage());

            throw new Exception($e->getMessage());
        }
    }

    // public static function findAll() {}

    // public static function find() {}
}
