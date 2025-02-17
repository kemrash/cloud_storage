<?php

namespace Services;

use Core\App;
use Core\Config;
use Core\Helper;
use Core\Response;
use Core\Response\JSONResponse;
use Core\Response\PageNotFoundResponse;
use Exception;

class UserService
{
    public function getUsersList(): array
    {
        return App::getService('userRepository')::findUsersBy('id', 'role', 'age', 'gender');
    }

    public function getUserById(string $id): ?array
    {
        $user = App::getService('userRepository')::getUserBy(['id' => (int) $id]);

        if ($user === null) {
            return null;
        }

        return ['id' => $user->id, 'role' => $user->role, 'age' => $user->age, 'gender' => $user->gender];
    }

    public function updateUser(array $params, int $id, string $role): Response
    {
        $errors = [];

        $user = App::getService('userRepository')::getUserBy(['id' => (int) $id]);

        if ($user === null) {
            return new PageNotFoundResponse();
        }

        foreach (Config::getConfig('database.dbColumns.user') as $parameter) {
            switch ($parameter) {
                case 'email':
                    if (!isset($params['email'])) {
                        break;
                    }

                    if ($role !== 'admin' && $user->email !== $params['email']) {
                        $errors[] = 'Только администратор может изменять email.';
                    }

                    try {
                        $user->email = $params['email'];
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }
                    break;

                case 'passwordEncrypted':
                    if (!isset($params['password'])) {
                        break;
                    }

                    try {
                        $user->passwordEncrypted = password_hash($params['password'], PASSWORD_DEFAULT);
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }
                    break;

                case 'role':
                    if (!isset($params['role'])) {
                        break;
                    }

                    if ($role !==  'admin' && $user->role !== $params['role']) {
                        $errors[] = 'Только администратор может изменять role.';
                        break;
                    }

                    try {
                        $user->role = $params['role'];
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }
                    break;

                case 'age':
                    if (!isset($params['age']) || $params['age'] === null) {
                        $user->age = null;
                        break;
                    }

                    $age = $params['age'];

                    if (!preg_match('/^\d+$/', $age) || $age < 0) {
                        $errors[] = 'Поле age должно быть целое число больше 0.';
                        break;
                    }

                    try {
                        $user->age = $age;
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }
                    break;

                case 'gender':
                    if (!isset($params['gender'])) {
                        $user->gender = null;
                        break;
                    }

                    try {
                        $user->gender = $params['gender'];
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }
                    break;
            }
        }

        if (count($errors) > 0) {
            return new JSONResponse(Helper::showError(implode(' ', $errors)), 400);
        }

        $data = App::getService('userRepository')::updateUser($user);

        if (isset($data['code']) && $data['code'] === '23000') {
            return new JSONResponse(Helper::showError('Пользователь с таким email уже существует'), 400);
        }

        if ($data['status'] === 'error') {
            return new JSONResponse(Helper::showError('Ошибка обновления пользователя'), 400);
        }

        $_SESSION['role'] = $user->role;

        return new JSONResponse($data);
    }

    public function loginUser(string $email, string $password): void
    {
        $user = App::getService('userRepository')::getUserBy(['email' => $email]);

        if ($user !== null && password_verify($password, $user->passwordEncrypted)) {
            if (password_needs_rehash($user->passwordEncrypted, PASSWORD_DEFAULT)) {
                $user->passwordEncrypted = password_hash($password, PASSWORD_DEFAULT);

                App::getService('userRepository')::updatePasswordById($user->id, $user->passwordEncrypted);
            }

            App::setService('user', $user);
        }
    }

    public function searchUserByEmail(string $email): Response
    {
        $user = App::getService('userRepository')::getUserBy(['email' => $email]);

        if ($user === null) {
            return new PageNotFoundResponse();
        }

        return new JSONResponse(['id' => $user->id]);
    }
}
