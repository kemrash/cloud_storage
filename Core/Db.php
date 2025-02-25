<?php

namespace Core;

use PDO;
use PDOException;
use Core\Config;
use Exception;

class Db
{
    private static array $allowedDatabases;
    private static ?Db $instance = null;
    public static PDO $connection;

    /**
     * Конструктор класса Db.
     * 
     * Создает подключение к базе данных с использованием параметров конфигурации.
     * 
     * @throws PDOException Если не удается установить соединение с базой данных.
     */
    private function __construct()
    {
        $dbConnection = Config::getConfig('database');
        $textConnection = 'mysql:host=' . $dbConnection['host'] . ';dbname=' . $dbConnection['name'] . ';charset=' . $dbConnection['charset'];

        self::$connection = new PDO($textConnection, $dbConnection['user'], $dbConnection['password'], [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        self::$allowedDatabases = Config::getConfig('database.dbNames');
    }

    /**
     * Метод клонирования объекта.
     * 
     * Этот метод закрыт для предотвращения клонирования экземпляров класса.
     * 
     * @return void
     */
    private function __clone(): void {}

    /**
     * Метод __wakeup
     *
     * Вызывается при десериализации объекта. Запрещает восстановление экземпляра класса.
     *
     * @throws Exception Исключение, выбрасываемое при попытке восстановления экземпляра класса.
     */
    public function __wakeup(): void
    {
        throw new Exception(__CLASS__ . ": Нельзя восстановить экземпляр");
    }

    /**
     * Возвращает экземпляр соединения с базой данных.
     *
     * Если экземпляр соединения еще не создан, создает новый.
     *
     * @return Db Экземпляр соединения с базой данных.
     */
    public static function getConnection(): Db
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Находит записи в базе данных по заданным столбцам и условиям.
     *
     * @param string $dbName Имя базы данных.
     * @param string[] $columns Массив имен столбцов для выборки.
     * @param string[] $allowedColumns Массив допустимых столбцов для выборки.
     * @param array<string, mixed> $where Ассоциативный массив условий для выборки (по умолчанию пустой массив).
     *
     * @return array Массив найденных записей.
     *
     * @throws Exception Если нет допустимых столбцов для выбора или произошла ошибка при выполнении запроса.
     */
    public static function findBy(string $dbName, array $columns, array $allowedColumns, array $where = []): array
    {
        self::validateDatabaseName($dbName);

        $safeColumns = array_intersect($allowedColumns, $columns);

        if (empty($safeColumns)) {
            throw new Exception(__CLASS__ . ': Нет допустимых столбцов для выбора.');
        }

        $columnList = implode(', ', array_map(fn($col) => "`$col`", $safeColumns));

        if (count($where) === 0) {
            $sql = "SELECT {$columnList} FROM {$dbName}";
        } else {
            $data = self::buildWhereClauseAndBindings($where, $allowedColumns);

            $sql = "SELECT {$columnList} FROM {$dbName} WHERE {$data['whereClause']}";
        }

        $statement = self::$connection->prepare($sql);

        try {
            if (count($where) === 0) {
                $statement->execute();
            } else {
                $statement->execute($data['bindings']);
            }

            return $statement->fetchAll();
        } catch (PDOException $e) {
            throw new Exception(__CLASS__ . ': ' . $e->getMessage());
        }
    }

    /**
     * Находит одну запись в базе данных по заданным параметрам.
     *
     * @param string $dbName Название базы данных.
     * @param array<string, mixed> $params Ассоциативный массив параметров для поиска (ключ - название столбца, значение - значение для поиска).
     * @param string[] $allowedColumns Массив допустимых названий столбцов для поиска.
     * @return array<string, mixed>|null Возвращает найденную запись в виде ассоциативного массива или null, если запись не найдена.
     * @throws Exception В случае ошибки выполнения запроса.
     */
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
            throw new Exception(__CLASS__ . ': ' . $e->getMessage());
        }
    }

    /**
     * Вставляет новую запись в указанную таблицу базы данных.
     *
     * @param string $dbName Название таблицы базы данных.
     * @param array<string, mixed> $params Ассоциативный массив параметров, где ключи - это названия колонок, а значения - значения для вставки.
     * @param string[] $allowedColumns Массив допустимых колонок для вставки.
     * @return string Возвращает идентификатор последней вставленной записи.
     */
    public static function insert(string $dbName, array $params, array $allowedColumns): string
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

        $sql = "INSERT INTO {$dbName} ({$dbColumns}) VALUES ({$preBinding})";

        $statement = self::$connection->prepare($sql);
        $statement->execute($bindings);

        $id = self::$connection->lastInsertId();

        return $id;
    }

    /**
     * Обновляет одну запись в базе данных по заданным условиям.
     *
     * @param string $dbName Название базы данных.
     * @param array<string, mixed> $paramsSet Ассоциативный массив с колонками и значениями для обновления.
     * @param array<string, mixed> $paramsWhere Ассоциативный массив с колонками и значениями для условий WHERE.
     * @param string[] $allowedColumns Массив допустимых колонок для обновления и условий.
     *
     * @return array<string, string> Возвращает массив с ключом 'status' и значением 'ok' в случае успешного выполнения, 
     * либо массив с ошибкой в случае ошибки уникальности.
     *
     * @throws Exception В случае возникновения ошибки при выполнении запроса.
     */
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

            throw new Exception(__CLASS__ . ': ' . $e->getMessage());
        }
    }

    /**
     * Удаляет одну запись из базы данных по заданным условиям.
     *
     * @param string $dbName Название таблицы в базе данных.
     * @param array<string, mixed> $paramsWhere Ассоциативный массив условий для удаления записи (ключ - название столбца, значение - значение для условия).
     * @param string[] $allowedColumns Массив допустимых названий столбцов для условий.
     *
     * @throws Exception В случае ошибки выполнения запроса.
     * @return void
     */
    public static function deleteOneBy(string $dbName, array $paramsWhere, array $allowedColumns): void
    {
        self::validateDatabaseName($dbName);

        $data = self::buildWhereClauseAndBindings($paramsWhere, $allowedColumns);

        $sql = "DELETE FROM {$dbName} WHERE {$data['whereClause']}";

        $statement = self::$connection->prepare($sql);

        try {
            $statement->execute($data['bindings']);
        } catch (PDOException $e) {
            throw new Exception(__CLASS__ . ': ' . $e->getMessage());
        }
    }

    /**
     * Строит SQL-условие WHERE и массив привязок для подготовленного запроса.
     *
     * @param array<string, mixed> $params Ассоциативный массив параметров, где ключи - это имена колонок, а значения - значения для фильтрации.
     * @param string[] $allowedColumns Массив допустимых имен колонок.
     * @param string $separator Разделитель условий (по умолчанию ' AND ').
     * 
     * @return array{whereClause: string, bindings: array<string, mixed>} Ассоциативный массив с ключами 'whereClause' (строка условия WHERE) и 'bindings' (массив привязок для подготовленного запроса).
     * 
     * @throws Exception Если в $params передана недопустимая колонка.
     */
    private static function buildWhereClauseAndBindings(array $params, array $allowedColumns, string $separator = ' AND '): array
    {
        $conditions = [];
        $bindings = [];

        foreach ($params as $key => $value) {
            if (!in_array($key, $allowedColumns, true)) {
                throw new Exception(__CLASS__ . ": Недопустимая колонка: $key");
            }

            $conditions[] = "{$key} = :{$key}";
            $bindings[$key] = $value;
        }

        $whereClause = implode($separator, $conditions);

        return ['whereClause' => $whereClause, 'bindings' => $bindings];
    }

    /**
     * Проверяет, является ли имя базы данных допустимым.
     *
     * @param string $dbName Имя базы данных для проверки.
     * 
     * @throws Exception Если имя базы данных недопустимо.
     */
    private static function validateDatabaseName(string $dbName): void
    {
        if (!in_array($dbName, self::$allowedDatabases, true)) {
            throw new Exception(__CLASS__ . ": Недопустимое имя базы данных");
        }
    }
}
