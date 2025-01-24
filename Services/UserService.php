<?php

namespace Services;

use Core\App;
use Core\Config;
use Core\ErrorApp;
use Core\Response;
use Exception;

class UserService
{
    public function getUsersList(): array
    {
        return App::getService('userRepository')::findUsersBy('role', 'age', 'gender');
    }

    public function getUserById(string $id): ?array
    {
        $user = App::getService('userRepository')::getUserBy(['id' => (int) $id]);

        if ($user === null) {
            return null;
        }

        return ['id' => $user->id, 'role' => $user->role, 'age' => $user->age, 'gender' => $user->gender];
    }

    public function updateUser(array $params, int $id, bool $isAdmin = false): Response
    {
        $errors = [];
        $user = App::getService('userRepository')::getUserBy(['id' => (int) $id]);

        if ($user === null) {
            return new Response('json', json_encode(ErrorApp::showError('Запрошенного пользователя не существует')), 400);
        }

        foreach (Config::getConfig('database.dbColumns.user') as $parameter) {
            switch ($parameter) {
                case 'email':
                    if (!isset($params['email'])) {
                        $errors[] = 'Не передано поле email.';
                        break;
                    }

                    try {
                        $user->email = $params['email'];
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }
                    break;

                case 'passwordEncrypted':
                    if (!isset($params['password'])) {
                        $errors[] = 'Не передано поле password.';
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
                        $errors[] = 'Не передано поле role.';
                        break;
                    }

                    try {
                        $user->role = $params['role'];
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }
                    break;

                case 'age':
                    if (!isset($params['age'])) {
                        $user->age = null;
                        break;
                    }

                    try {
                        $user->age = $params['age'];
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
            return new Response('json', json_encode(ErrorApp::showError(implode(' ', $errors))), 400);
        }

        $data = App::getService('userRepository')::updateUser($user);

        if ($data['status'] === 'error') {
            return new Response('json', json_encode($data), 400);
        }

        return new Response('json', json_encode($data));
    }

    public function loginUser(string $email, string $password): void
    {
        $user = App::getService('userRepository')::getUserBy(['email' => $email]);

        if ($user !== null && password_verify($password, $user->passwordEncrypted)) {
            if (password_needs_rehash($user->passwordEncrypted, PASSWORD_DEFAULT)) {
                $user->passwordEncrypted = password_hash($password, PASSWORD_DEFAULT);

                $data = App::getService('userRepository')::updateUser($user);

                if ($data['status'] !== 'ok') {
                    ErrorApp::writeLog((get_class() . ': Произошла ошибка при обновлении хеша пароля.'));
                }
            }

            App::setService('user', $user);
        }
    }
}
