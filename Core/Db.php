<?php

namespace Core;

use Exception;
use PDO;
use PDOException;

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
        $textError = "Нельзя восстановить экземпляр";

        ErrorApp::writeLog($textError);

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
        if (!in_array($dbName, self::ALLOWED_DATABASES, true)) {
            ErrorApp::writeLog("Недопустимое имя базы данных");

            return [];
        }

        $safeColumns = array_intersect($allowedColumns, $columns);

        if (empty($safeColumns)) {
            ErrorApp::writeLog("Нет допустимых столбцов для выбора.");

            return [];
        }

        $columnList = implode(', ', array_map(fn($col) => "`$col`", $safeColumns));

        $sql = "SELECT {$columnList} FROM {$dbName}";

        try {
            $statement = self::$connection->prepare($sql);
            $statement->execute();

            return $statement->fetchAll();
        } catch (PDOException $e) {
            ErrorApp::writeLog($e->getMessage());

            return [];
        }
    }

    public static function findOneBy(string $dbName, array $params, array $allowedColumns): ?array
    {
        if (!in_array($dbName, self::ALLOWED_DATABASES, true)) {
            $textError = "Недопустимое имя базы данных";

            ErrorApp::writeLog($textError);

            return ErrorApp::showError($textError);
        }

        $conditions = [];
        $bindings = [];

        foreach ($params as $key => $value) {
            if (!in_array($key, $allowedColumns, true)) {
                $textError = "Недопустимая колонка: $key";

                ErrorApp::writeLog($textError);

                return ErrorApp::showError($textError);
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

            return $result ?: null;
        } catch (PDOException $e) {
            ErrorApp::writeLog($e->getMessage());

            return null;
        }
    }

    public static function updateOneBy(string $dbName, array $paramsSet, array $paramsWhere, array $allowedColumns): array
    {
        if (!in_array($dbName, self::ALLOWED_DATABASES, true)) {
            $textError = "Недопустимое имя базы данных";

            ErrorApp::writeLog(get_class() . ': ' . $textError);

            return ErrorApp::showError($textError);
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

                    ErrorApp::writeLog(get_class() . ': ' . $textError);

                    return ErrorApp::showError($textError);
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
            ErrorApp::writeLog(get_class() . ': ' . $e->getMessage());

            return ErrorApp::showError();
        }
    }

    // public static function findAll() {}

    // public static function find() {}
}
