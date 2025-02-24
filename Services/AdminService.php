<?php

namespace Services;

use Core\App;
use Core\AppException;
use Core\Config;
use Core\Db;
use Core\FileStorage;
use Core\Helper;
use Core\Response;
use Exception;
use Models\User;
use PDOException;

class AdminService
{
    /**
     * Возвращает список пользователей.
     *
     * @return array<int, array<string, mixed>> Массив пользователей, где каждый пользователь представлен в виде ассоциативного массива с ключами 'id', 'email', 'role', 'age', 'gender'.
     */
    public function getUsersList(): array
    {
        return App::getService('userRepository')::findUsersBy('id', 'email', 'role', 'age', 'gender');
    }

    /**
     * Создает нового пользователя на основе переданных параметров.
     *
     * @param array{
     *     email: string,
     *     password: string,
     *     role: string,
     *     age?: int,
     *     gender?: string
     * } $params Массив параметров для создания пользователя. Обязательные ключи: email, password, role. Необязательные ключи: age, gender.
     *
     * @return Response JSON-ответ с данными созданного пользователя или сообщением об ошибке.
     *
     * @throws AppException В случае возникновения ошибки при создании пользователя.
     */
    public function createUser(array $params): Response
    {
        $dbColumns = Config::getConfig('database.dbColumns.user');

        $allowedParams = [];
        $errors = [];

        foreach ($dbColumns as $column) {
            switch ($column) {
                case 'email':
                    if (!isset($params['email']) || !User::isValidEmail($params['email'])) {
                        $errors[] = 'Поле email обязательно для заполнения и должно быть корректным email-адресом и длинной не более 150 символов.';
                        break;
                    }

                    $allowedParams['email'] = $params['email'];
                    break;

                case 'passwordEncrypted':
                    if (!isset($params['password'])) {
                        $errors[] = 'Поле password обязательно для заполнения.';
                        break;
                    }

                    $allowedParams['passwordEncrypted'] = password_hash($params['password'], PASSWORD_DEFAULT);
                    break;

                case 'role':
                    if (!isset($params['role']) || !User::isValidRole($params['role'])) {
                        $errors[] = 'Поле role обязательно для заполнения и должно быть user или admin.';
                        break;
                    }

                    $allowedParams['role'] = $params['role'];
                    break;

                case 'age':
                    if (!isset($params['age'])) {
                        break;
                    }

                    if (!preg_match('/^\d+$/', $params['age']) || (int) $params['age'] < 0) {
                        $errors[] = 'Поле age должно быть положительным целым числом.';
                        break;
                    }

                    $allowedParams['age'] = (int) $params['age'];
                    break;

                case 'gender':
                    if (!isset($params['gender'])) {
                        break;
                    }

                    if (!User::isValidGender($params['gender'])) {
                        $errors[] = 'Поле gender должно быть male или female.';
                        break;
                    }

                    $allowedParams['gender'] = $params['gender'];
                    break;
            }
        }

        if (count($errors) > 0) {
            return new Response('json', Helper::showError(implode(' ', $errors)));
        }

        try {
            $data =  App::getService('adminRepository')::createUser($allowedParams);
        } catch (PDOException $e) {
            $connection = Db::$connection;

            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            if ($e->getCode() === '23000') {
                return new Response('json', Helper::showError('Пользователь с таким email уже существует'));
            }

            throw new AppException(__CLASS__, $e->getMessage());
        }

        return new Response('json', $data);
    }

    /**
     * Получает пользователя по его идентификатору.
     *
     * @param string $id Идентификатор пользователя.
     * @return array{id: int, email: string, role: string, age: int, gender: string}|null Массив с данными пользователя или null, если пользователь не найден.
     */
    public function getUserById(string $id): ?array
    {
        $user = App::getService('userRepository')::getUserBy(['id' => (int) $id]);

        if ($user === null) {
            return null;
        }

        return ['id' => $user->id, 'email' => $user->email, 'role' => $user->role, 'age' => $user->age, 'gender' => $user->gender];
    }

    /**
     * Удаляет пользователя по его идентификатору.
     *
     * @param string $id Идентификатор пользователя.
     * @return Response JSON-ответ с результатом операции.
     * @throws AppException В случае ошибки при удалении пользователя.
     */
    public function deleteUserById(string $id): Response
    {
        if ((int) $id === Config::getConfig('app.idUserSystem')) {
            return new Response('json', Helper::showError('Нельзя удалять системного пользователя'));
        }

        $filesList = [];

        $connection = Db::$connection;
        $connection->beginTransaction();

        try {
            $foldersList = App::getService('folderRepository')::getUserFoldersList((int) $id);

            foreach ($foldersList as $folder) {
                $newFiles = App::getService('folderService')->deleteFolderAndReturnFilesList($folder['id'], true);

                $filesList = [...$filesList, ...$newFiles];
            }

            App::getService('adminRepository')::deleteUserBy(['id' => $id]);

            $connection->commit();
        } catch (Exception $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw new AppException(__CLASS__, $e->getMessage());
        }

        if (count($filesList) > 0) {
            $fileStorage = new FileStorage();
            $fileStorage->deleteFiles($filesList);
        }

        return new Response();
    }
}
