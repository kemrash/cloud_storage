<?php

namespace Controllers;

use Core\App;
use Core\Config;
use Core\Helper;
use Core\Request;
use Core\Response;
use Models\User;
use traits\StatusResponseTrait;
use traits\UserTrait;

class UserController
{
    use UserTrait;
    use StatusResponseTrait;

    /**
     * Метод для получения списка пользователей.
     *
     * @return Response Возвращает объект ответа в формате JSON, содержащий данные пользователей.
     */
    public function list(): Response
    {
        $data = App::getService('user')->list('id', 'role', 'age', 'gender');

        return new Response('json', $data);
    }

    /**
     * Получает информацию о пользователе по его идентификатору.
     *
     * @param array<int, string> $params Массив параметров, где первый элемент - идентификатор пользователя.
     * @return Response Ответ с информацией о пользователе в формате JSON или сообщение об ошибке.
     */
    public function get(array $params): Response
    {
        $userId = $params[0];

        if (!ctype_digit($userId)) {
            return $this->pageNotFound();
        }

        $userId = (int) $userId;
        $user = new User();

        if (!$user->get(['id' => $userId])) {
            return $this->pageNotFound();
        }

        return new Response(
            'json',
            [
                'id' => $user->id,
                'role' => $user->role,
                'age' => $user->age,
                'gender' => $user->gender
            ]
        );
    }

    /**
     * Обновляет данные пользователя.
     *
     * @param Request $request Объект запроса, содержащий данные для обновления.
     * 
     * @param array<int, string> $errors Массив ошибок. 
     * 
     * @param array<string, string|int> $data Данные для обновления пользователя.
     * 
     * @return Response Ответ с результатом обновления.
     * 
     */
    public function update(Request $request): Response
    {
        $user = App::getService('user');
        $data = $request->getData()['PUT'];

        if (
            !$this->isLogin() ||
            !isset($data['id']) ||
            !ctype_digit($data['id']) ||
            $_SESSION['id'] !== (int) $data['id']
        ) {
            return $this->accessForbidden();
        }

        $errors = $user->allValidation($data);

        if (count($errors) > 0) {
            return new Response('json', Helper::showError(implode(' ', $errors)), 400);
        }

        $result = $user->update();

        if (isset($result['code']) && $result['code'] === '23000') {
            return new Response('json', Helper::showError('Пользователь с таким email уже существует'), 400);
        }

        $_SESSION['role'] = $user->role;

        return new Response();
    }

    /**
     * Метод для выполнения входа пользователя.
     *
     * @param Request $request Объект запроса, содержащий данные для входа.
     * 
     * @return Response Ответ в формате JSON с данными пользователя или сообщением об ошибке.
     */
    public function login(Request $request): Response
    {
        $email = null;
        $password = null;
        $textError = 'Не все обязательные поля заполнены, или их значения не корректны';
        $requestParams = $request->getData()['POST'];
        $user = App::getService('user');

        if (isset($requestParams['email']) && $user->isValidEmail($requestParams['email'])) {
            $email = trim($requestParams['email']);
        }

        if (isset($requestParams['password']) && $user->isValidPassword($requestParams['password'])) {
            $password = trim($requestParams['password']);
        }

        if ($email === null || $password === null) {
            return new Response('json', Helper::showError($textError), 400);
        }

        $user = App::getService('user');

        if (!$user->login($email, $password) || $user->id === 0) {
            return new Response('json', Helper::showError($textError), 400);
        }

        $_SESSION['id'] = $user->id;
        $_SESSION['role'] = $user->role;

        return new Response();
    }

    /**
     * Завершает текущую сессию пользователя и возвращает JSON-ответ.
     *
     * @return Response JSON-ответ, подтверждающий завершение сессии.
     */
    public function logout(): Response
    {
        App::getService('session')->destroySession();

        return new Response();
    }

    /**
     * Ищет пользователя по email и возвращает его ID в формате JSON.
     *
     * @param array{0: string} $params Массив параметров, где первый элемент - email пользователя.
     * @return Response Ответ с JSON, содержащим ID пользователя, или сообщение об ошибке.
     */
    public function searchByEmail(array $params): Response
    {
        if (!$this->isLogin()) {
            return $this->accessForbidden();
        }

        $user = new User();

        if (!$user->get(['email' => $params[0]])) {
            return $this->pageNotFound();
        }

        return new Response('json', ['id' => $user->id]);;
    }

    /**
     * Подготавливает сброс пароля.
     *
     * @param Request $request Объект запроса, содержащий данные запроса.
     * 
     * @return Response JSON-ответ с результатом операции.
     */
    public function preparationResetPassword(Request $request): Response
    {
        if ($this->isLogin()) {
            return new Response('json', Helper::showError('Вошедший пользователь не может сбросить пароль'), 403);
        }

        $email = null;

        if (!isset($request->getData()['GET']['email'])) {
            return new Response('json', Helper::showError('Не передан email'), 400);
        }

        $email = trim($request->getData()['GET']['email']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new Response('json', Helper::showError('Email не корректен'), 400);
        }

        $url = $request->getData()['originUrl'] . $request->getRoute();
        $expiresInMinutes = Config::getConfig('resetPassword.expiresInMinutes');

        $data = App::getService('resetPassword')->preparationAndSendEmail($email, $url, $expiresInMinutes);

        if ($data === null) {
            return new Response('json', Helper::showError(
                'Не найден пользователь с email = ' .
                    $email .
                    ', или ещё не прошло ' .
                    $expiresInMinutes .
                    ' минут с момента последнего запроса'
            ), 400);
        }

        return new Response('json', $data);
    }

    /**
     * Сбрасывает пароль пользователя.
     *
     * @param Request $request Объект запроса, содержащий данные для сброса пароля.
     *                         Ожидается, что в массиве GET будут присутствовать ключи:
     *                         - 'id' (int): Идентификатор пользователя.
     *                         - 'token' (string): Токен для сброса пароля.
     *                         В массиве PUT должен присутствовать ключ:
     *                         - 'password' (string): Новый пароль пользователя.
     *
     * @return Response JSON-ответ с результатом операции.
     *                  В случае ошибки возвращает JSON-ответ с описанием ошибки и соответствующим HTTP статусом.
     */
    public function resetPassword(Request $request): Response
    {
        if ($this->isLogin()) {
            return new Response('json', Helper::showError('Вошедший пользователь не может сбросить пароль'), 403);
        }

        $id = null;
        $token = null;
        $password = null;
        $errors = [];

        if (!isset($request->getData()['GET']['id']) || !preg_match('/^\d+$/', $request->getData()['GET']['id'])) {
            $errors[] = 'Не передан id или его значение не корректно';
        }

        if (!isset($request->getData()['GET']['token']) || !is_string($request->getData()['GET']['token'])) {
            $errors[] = 'Не передан token или его значение не корректно';
        }

        if (!isset($request->getData()['PUT']['password']) || !is_string($request->getData()['PUT']['password'])) {
            $errors[] = 'Не передан password или его значение не корректно';
        }

        if (count($errors) > 0) {
            return new Response('json', Helper::showError(implode(', ', $errors)), 400);
        }

        $id = (int) trim($request->getData()['GET']['id']);
        $token = trim($request->getData()['GET']['token']);
        $password = trim($request->getData()['PUT']['password']);

        $data =  App::getService('resetPassword')->resetPassword($id, $token, $password);

        if ($data === null) {
            return new Response('json', Helper::showError('Не найден пользователь или неверный, истёкший токен'), 400);
        }

        if ($data['status'] !== 'ok') {
            return new Response('json', $data, 400);
        }

        return new Response('json', $data);
    }
}
