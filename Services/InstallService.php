<?php

namespace Services;

use Core\Helper;
use Core\Response;
use Core\Response\JSONResponse;
use Models\User;
use PDO;
use PDOException;

class InstallService
{
    public function install(array $data): Response
    {
        $pathConfig = './config.php';
        $config = require_once $pathConfig . '.bak';

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'dbHost':
                    $config['database']['host'] = trim($value);
                    break;
                case 'dbName':
                    $config['database']['name'] = trim($value);
                    break;
                case 'dbUser':
                    $config['database']['user'] = trim($value);
                    break;
                case 'dbPassword':
                    $config['database']['password'] = trim($value);
                    break;
                case 'smtpHost':
                    $config['mailSMTP']['host'] = trim($value);
                    break;
                case 'smtpPort':
                    $config['mailSMTP']['port'] = trim($value);
                    break;
                case 'smtpUser':
                    $config['mailSMTP']['user'] = trim($value);
                    break;
                case 'smtpPassword':
                    $config['mailSMTP']['password'] = trim($value);
                    break;
                case 'smtpFrom':
                    $config['mailSMTP']['from'] = trim($value);
                    break;
                case 'adminPassword':
                    $data['adminPassword'] = password_hash(trim($value), PASSWORD_DEFAULT);
                    break;
            }
        }

        if (!isset($data['adminUser']) || !User::isValidEmail($data['adminUser'])) {
            return new JSONResponse(Helper::showError('Поле adminUser обязательно для заполнения, валидный email длиной меньше 150 символов.'), 400);
        }

        if (!isset($data['adminPassword']) || !User::isValidPassword($data['adminPassword'])) {
            return new JSONResponse(Helper::showError('Поле adminPassword обязательно для заполнения и меньше 255 символов.'), 400);
        }

        $textConnection = 'mysql:host=' . $config['database']['host'] . ';dbname=' . $config['database']['name'] . ';charset=' . $config['database']['charset'];

        try {
            $connection = new PDO($textConnection, $config['database']['user'], $config['database']['password']);
        } catch (PDOException $_) {

            return new JSONResponse(Helper::showError('Не удалось подключиться к базе данных, проверьте правильность введенных данных.' .
                'Поля dbHost, dbName, dbUser, dbPassword обязательны '), 400);
        }

        if (file_exists('./Sql/install.sql') === false) {
            $textError = 'Не удалось найти файл базы данных';
            Helper::writeLog(__CLASS__ . ': ' . $textError);

            return new JSONResponse(Helper::showError($textError), 500);
        }

        if ($connection->exec(file_get_contents('./Sql/install.sql')) === false) {
            $textError = 'Не удалось создать базу данных';
            Helper::writeLog(__CLASS__ . ': ' . $textError);

            return new JSONResponse(Helper::showError($textError), 500);
        }

        if (file_put_contents($pathConfig, "<?php" . PHP_EOL . PHP_EOL . 'return ' . var_export($config, true) . ';') === false) {
            $textError = 'Не удалось сохранить файл конфигураций config.php';
            Helper::writeLog(__CLASS__ . ': ' . $textError);

            return new JSONResponse(Helper::showError($textError), 500);
        }

        $statement = $connection->prepare("INSERT INTO `user`(`id`, `email`, `passwordEncrypted`, `role`, `age`, `gender`) VALUES (null, :email, :password, 'admin', null, null)");
        $statement->execute(['email' => $data['adminUser'], 'password' => $data['adminPassword']]);

        $userId = $connection->lastInsertId();

        $statement = $connection->prepare("INSERT INTO `folder`(`id`, `userId`, `parentId`, `name`) VALUES (null, :userId, 0, 'home')");
        $statement->execute(['userId' => $userId]);

        return new JSONResponse();
    }
}
