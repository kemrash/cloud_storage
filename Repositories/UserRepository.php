<?php

namespace Repositories;

use Core\Db;
use Exception;

class UserRepository extends DB
{
    public static function findBy(...$columns)
    {
        $allowedColumns = ['id', 'login', 'password_encrypted', 'role', 'age', 'gender'];

        $safeColumns = array_intersect($allowedColumns, $columns);

        if (empty($safeColumns)) {
            throw new Exception('Нет допустимых столбцов для выбора.');
        }

        $columnList = implode(', ', array_map(fn($col) => "`$col`", $safeColumns));

        $sql = "SELECT {$columnList} FROM user";

        $statement = parent::$connection->prepare($sql);
        $statement->execute();

        return $statement->fetchAll();
    }
}
