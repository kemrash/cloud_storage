<?php

namespace Core;

use PDO;
use PDOException;

class Install
{
    private const PATH_CONFIG = './config.php';
    private array $data;
    private array $config;

    /**
     * Конструктор класса.
     *
     * @param array<string, mixed> $data Массив данных для инициализации.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->config = require_once self::PATH_CONFIG . '.bak';
    }

    /**
     * Выполняет установку системы, включая проверку данных, подключение к базе данных,
     * выполнение SQL-скрипта и создание начальных записей в базе данных.
     *
     * @return array{
     *     status: string
     * } Возвращает массив с ключом 'status', который указывает на результат выполнения ('ok' или сообщение об ошибке).
     */
    public function run(): array
    {
        $result = $this->validateAndPrepareData();

        if ($result['status'] !== 'ok') {
            return $result;
        }

        $textConnection = 'mysql:host=' .
            $this->config['database']['host'] .
            ';dbname=' .
            $this->config['database']['name'] .
            ';charset=' .
            $this->config['database']['charset'];

        try {
            $connection = new PDO($textConnection, $this->config['database']['user'], $this->config['database']['password']);
            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $_) {

            return Helper::showError('Не удалось подключиться к базе данных, проверьте правильность введенных данных.' .
                'Поля dbHost, dbName, dbUser, dbPassword обязательны ');
        }

        $sqlPath = './sql/install.sql';

        if (file_exists($sqlPath) === false) {
            $textError = 'Не удалось найти файл базы данных';
            Helper::writeLog(__CLASS__ . ': ' . $textError);

            return Helper::showError($textError);
        }

        if ($connection->exec(file_get_contents($sqlPath)) === false) {
            $textError = 'Не удалось создать базу данных';
            Helper::writeLog(__CLASS__ . ': ' . $textError);

            return Helper::showError($textError);
        }

        if (file_put_contents(self::PATH_CONFIG, "<?php" . PHP_EOL . PHP_EOL . 'return ' . var_export($this->config, true) . ';') === false) {
            $textError = 'Не удалось сохранить файл конфигураций config.php';
            Helper::writeLog(__CLASS__ . ': ' . $textError);

            return Helper::showError($textError);
        }

        $statement = $connection->prepare("INSERT INTO `user`(`id`, `email`, `passwordEncrypted`, `role`, `age`, `gender`) VALUES (null, :email, :password, 'admin', null, null)");
        $statement->execute(['email' => $this->data['adminUser'], 'password' => $this->data['adminPassword']]);

        $userId = $connection->lastInsertId();

        $statement = $connection->prepare("INSERT INTO `folder`(`id`, `userId`, `parentId`, `name`) VALUES (null, :userId, 0, 'home')");
        $statement->execute(['userId' => $userId]);

        return ['status' => 'ok'];
    }

    /**
     * Валидирует и подготавливает данные для установки.
     *
     * @return array Статус выполнения операции.
     *
     * @throws InvalidArgumentException Если одно из обязательных полей не заполнено или имеет неверный формат.
     *
     * Поля в массиве $this->data:
     * - string 'dbHost' Обязательное поле, строка.
     * - string 'dbName' Обязательное поле, строка.
     * - string 'adminUser' Обязательное поле, строка, валидный email, длина меньше 150 символов.
     * - string 'adminPassword' Обязательное поле, строка, длина меньше 255 символов.
     *
     * Поля в массиве $this->config:
     * - array 'database' Настройки базы данных:
     *   - string 'host' Хост базы данных.
     *   - string 'name' Имя базы данных.
     *   - string 'user' Пользователь базы данных.
     *   - string 'password' Пароль базы данных.
     * - array 'mailSMTP' Настройки SMTP:
     *   - string 'host' Хост SMTP.
     *   - int 'port' Порт SMTP.
     *   - string 'user' Пользователь SMTP.
     *   - string 'password' Пароль SMTP.
     *   - string 'from' Адрес отправителя.
     */
    public function validateAndPrepareData()
    {
        if (
            !isset($this->data['dbHost']) ||
            !is_string($this->data['dbHost'])
        ) {
            return Helper::showError('Поле dbHost обязательно для заполнения.');
        }

        if (
            !isset($this->data['dbName']) ||
            !is_string($this->data['dbName'])
        ) {
            return Helper::showError('Поле dbName обязательно для заполнения.');
        }

        if (
            !isset($this->data['adminUser']) ||
            !is_string($this->data['adminUser']) ||
            !filter_var($this->data['adminUser'], FILTER_VALIDATE_EMAIL) ||
            mb_strlen($this->data['adminUser'], 'UTF-8') >= 150
        ) {
            return Helper::showError('Поле adminUser обязательно для заполнения, валидный email длиной меньше 150 символов.');
        }

        if (
            !isset($this->data['adminPassword']) ||
            !is_string($this->data['adminPassword']) ||
            mb_strlen($this->data['adminPassword'], 'UTF-8') >= 255
        ) {
            return Helper::showError('Поле adminPassword обязательно для заполнения и меньше 255 символов.');
        }

        foreach ($this->data as $key => $value) {
            switch ($key) {
                case 'dbHost':
                    $this->config['database']['host'] = trim($value);
                    break;
                case 'dbName':
                    $this->config['database']['name'] = trim($value);
                    break;
                case 'dbUser':
                    $this->config['database']['user'] = trim($value);
                    break;
                case 'dbPassword':
                    $this->config['database']['password'] = trim($value);
                    break;
                case 'smtpHost':
                    $this->config['mailSMTP']['host'] = trim($value);
                    break;
                case 'smtpPort':
                    $this->config['mailSMTP']['port'] = trim($value);
                    break;
                case 'smtpUser':
                    $this->config['mailSMTP']['user'] = trim($value);
                    break;
                case 'smtpPassword':
                    $this->config['mailSMTP']['password'] = trim($value);
                    break;
                case 'smtpFrom':
                    $this->config['mailSMTP']['from'] = trim($value);
                    break;
                case 'adminPassword':
                    $this->data['adminPassword'] = password_hash(trim($value), PASSWORD_DEFAULT);
                    break;
            }
        }

        return ['status' => 'ok'];
    }
}
