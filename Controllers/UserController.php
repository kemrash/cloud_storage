<?php

namespace Controllers;

use Core\App;
use Core\Helper;
use Core\Request;
use Core\Response;
use Core\Response\JSONResponse;
use Models\User;
use Traits\UserTrait;

class UserController
{
    use UserTrait;

    public function list(): Response
    {
        $data = App::getService('userService')->getUsersList();
        $response = new JSONResponse($data);

        return $response;
    }

    public function get(array $params): Response
    {
        $data = App::getService('userService')->getUserById($params[0]);

        if ($data === null) {
            return new Response('html', 'Страница не найдена', 404);
        }

        return new JSONResponse($data);
    }

    public function update(Request $request): Response
    {
        if (!isset($_SESSION['id']) || isset($request->getData()['PUT']['id']) && $_SESSION['id'] !== (int) $request->getData()['PUT']['id']) {
            return new JSONResponse(Helper::showError('Доступ запрещен'), 403);
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
            return new JSONResponse(Helper::showError('Не все обязательные поля заполнены, или их значения не корректны'), 400);
        }

        App::getService('userService')->loginUser($email, $password);

        if (!App::issetClass('user')) {
            return new JSONResponse(Helper::showError('Неправильный логин или пароль'), 401);
        }

        $user = App::getService('user');

        $_SESSION['id'] = $user->id;
        $_SESSION['role'] = $user->role;

        return new JSONResponse();
    }

    public function logout(): Response
    {
        App::getService('session')->destroySession();

        return new JSONResponse();
    }

    public function searchByEmail(array $params): Response
    {
        if ($response = $this->checkUserAuthorization()) {
            return $response;
        }

        return App::getService('userService')->searchUserByEmail($params[0]);
    }
}
