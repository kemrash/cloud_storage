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
        return App::getService('userRepository')::findUsersBy('id', 'role', 'age', 'gender');
    }

    public function getUserById(string $id): ?array
    {
        $user = App::getService('userRepository')::getUserBy(['id' => (int) $id]);

        if (is_array($user) && isset($user['status']) && $user['status'] === 'error' || $user === null) {
            return null;
        }

        return ['id' => $user->id, 'role' => $user->role, 'age' => $user->age, 'gender' => $user->gender];
    }

    public function updateUser(array $params, int $id, string $role): Response
    {
        $errors = [];
        $user = App::getService('userRepository')::getUserBy(['id' => (int) $id]);

        if (is_array($user) && isset($user['status']) && $user['status'] === 'error') {
            return new Response('json', json_encode(ErrorApp::showError('Произошла ошибка сервера')), 500);
        }

        if ($user === null) {
            return new Response('json', json_encode(ErrorApp::showError('Запрошенного пользователя не существует')), 400);
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

        if (isset($data['code']) && $data['code'] === '23000') {
            return new Response('json', json_encode(ErrorApp::showError('Пользователь с таким email уже существует')), 400);
        }

        if ($data['status'] === 'error') {
            return new Response('json', json_encode($data), 400);
        }

        $_SESSION['role'] = $user->role;

        return new Response('json', json_encode($data));
    }

    public function loginUser(string $email, string $password): array
    {
        $user = App::getService('userRepository')::getUserBy(['email' => $email]);

        if (is_array($user) && isset($user['status']) && $user['status'] === 'error') {
            return $user;
        }

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

        return ['status' => 'ok'];
    }
}
