<?php

namespace Controllers;

use Core\App;
use Core\ErrorApp;
use Core\Request;
use Core\Response;
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
        $data = App::getService('userService')->getUserById($params[0]);

        if ($data !== null) {
            $response = new Response('json', json_encode($data));
        } else {
            $response = new Response('html', 'Страница не найдена', 404);
        }

        return $response;;
    }

    public function update(Request $request): Response
    {
        App::getService('session')->startSession();

        if (!isset($_SESSION['id'])) {
            return new Response('json', json_encode(ErrorApp::showError('Доступ запрещен')), 403);
        }

        return App::getService('userService')->updateUser($request->getData()['PUT'], (int) $_SESSION['id']);
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

        App::getService('userService')->loginUser($email, $password);

        if (!App::issetClass('user')) {
            return new Response('json', json_encode(ErrorApp::showError('Неправильный логин или пароль')), 401);
        }

        App::getService('session')->startSession();

        $user = App::getService('user');

        if (!isset($_SESSION['id'])) {
            $_SESSION['id'] = $user->id;
        }

        return new Response('json', json_encode(['status' => 'ok']));
    }

    public function logout(): Response
    {
        App::getService('session')->destroySession();

        return new Response('json', json_encode(['status' => 'ok']));
    }
}
