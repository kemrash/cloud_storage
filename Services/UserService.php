<?php

namespace Services;

use Core\App;
use Core\Config;
use Core\Helper;
use Core\Response;
use Exception;

class UserService
{
    /**
     * Возвращает список пользователей.
     *
     * @return array<int, array<string, string|int>> Массив пользователей, 
     * где каждый пользователь представлен в виде ассоциативного массива с ключами 'id', 'role', 'age', 'gender'.
     */
    public function getUsersList(): array
    {
        return App::getService('userRepository')::findUsersBy('id', 'role', 'age', 'gender');
    }

    /**
     * Получает пользователя по его идентификатору.
     *
     * @param string $id Идентификатор пользователя.
     * @return array<string, string|int>|null Ассоциативный массив с данными пользователя (id, role, age, gender) или null, если пользователь не найден.
     */
    public function getUserById(string $id): ?array
    {
        $user = App::getService('userRepository')::getUserBy(['id' => (int) $id]);

        if ($user === null) {
            return null;
        }

        return ['id' => $user->id, 'role' => $user->role, 'age' => $user->age, 'gender' => $user->gender];
    }

    /**
     * Обновляет данные пользователя.
     *
     * @param array<string, string|int> $params Ассоциативный массив параметров для обновления пользователя.
     * @param int $id Идентификатор пользователя.
     * @param string $role Роль текущего пользователя, выполняющего обновление.
     * @return Response Ответ с результатом обновления.
     */
    public function updateUser(array $params, int $id, string $role): Response
    {
        $errors = [];

        $user = App::getService('userRepository')::getUserBy(['id' => (int) $id]);

        if ($user === null) {
            return new Response('renderError', 'Страница не найдена', 404);
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
            return new Response('json', Helper::showError(implode(' ', $errors)), 400);
        }

        $data = App::getService('userRepository')::updateUser($user);

        if (isset($data['code']) && $data['code'] === '23000') {
            return new Response('json', Helper::showError('Пользователь с таким email уже существует'), 400);
        }

        if ($data['status'] === 'error') {
            return new Response('json', Helper::showError('Ошибка обновления пользователя'), 400);
        }

        $_SESSION['role'] = $user->role;

        return new Response('json', $data);
    }

    /**
     * Авторизует пользователя по указанным email и паролю.
     *
     * @param string $email Электронная почта пользователя.
     * @param string $password Пароль пользователя.
     *
     * @return void
     */
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

    /**
     * Ищет пользователя по адресу электронной почты.
     *
     * @param string $email Адрес электронной почты пользователя.
     * @return Response Возвращает JSON-ответ с идентификатором пользователя или ответ о не нахождении страницы.
     */
    public function searchUserByEmail(string $email): Response
    {
        $user = App::getService('userRepository')::getUserBy(['email' => $email]);

        if ($user === null) {
            return new Response('renderError', 'Страница не найдена', 404);
        }

        return new Response('json', ['id' => $user->id]);
    }
}
