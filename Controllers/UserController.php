<?php

namespace Controllers;

use Core\App;
use Core\ErrorApp;
use Core\Request;
use Core\Response;
use Exception;
use Models\User;

class UserController
{
    public function list(): Response
    {
        $data = App::getService('userService')->getUsersList();
        $response = new Response('json', json_encode($data));

        return $response;
    }

    public function get(array $params): Response
    {
        try {
            $data = App::getService('userService')->getUserById($params[0]);

            if ($data !== null) {
                $response = new Response('json', json_encode($data));
            } else {
                $response = new Response('html', 'Страница не найдена', 404);
            }

            return $response;
        } catch (Exception $e) {
            ErrorApp::writeLog(self::class . ': ' . $e->getMessage());

            return new Response('json', json_encode(ErrorApp::showError('Произошла ошибка сервера')), 500);
        }
    }

    public function update(Request $request): Response
    {
        try {
            App::getService('session')->startSession();
        } catch (Exception $_) {
            return new Response('json', json_encode(ErrorApp::showError('Произошла ошибка сервера')), 500);
        }

        if (!isset($_SESSION['id']) || isset($request->getData()['PUT']['id']) && $_SESSION['id'] !== (int) $request->getData()['PUT']['id']) {
            return new Response('json', json_encode(ErrorApp::showError('Доступ запрещен')), 403);
        }

        return App::getService('userService')->updateUser($request->getData()['PUT'], (int) $_SESSION['id'], $_SESSION['role']);
    }

    public function login(Request $request): Response
    {
        $email = null;
        $password = null;
        $requestParams = $request->getData()['POST'];

        if (isset($requestParams['email']) && User::isValidEmail($requestParams['email'])) {
            $email = $requestParams['email'];
        }

        if (isset($requestParams['password']) && User::isValidPassword($requestParams['password'])) {
            $password = $requestParams['password'];
        }

        if ($email === null || $password === null) {
            return new Response('json', json_encode(ErrorApp::showError('Не все обязательные поля заполнены, или их значения не корректны')), 400);
        }

        $data = App::getService('userService')->loginUser($email, $password);

        if (isset($data['status']) && $data['status'] === 'error') {
            return new Response('json', json_encode(ErrorApp::showError('Произошла ошибка сервера')), 500);
        }

        if (!App::issetClass('user')) {
            return new Response('json', json_encode(ErrorApp::showError('Неправильный логин или пароль')), 401);
        }

        try {
            App::getService('session')->startSession();
        } catch (Exception $_) {
            return new Response('json', json_encode(ErrorApp::showError('Произошла ошибка сервера')), 500);
        }

        $user = App::getService('user');

        $_SESSION['id'] = $user->id;
        $_SESSION['role'] = $user->role;

        return new Response('json', json_encode(['status' => 'ok']));
    }

    public function logout(): Response
    {
        App::getService('session')->destroySession();

        return new Response('json', json_encode(['status' => 'ok']));
    }
}
