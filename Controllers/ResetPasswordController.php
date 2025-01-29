<?php

namespace Controllers;

use Core\App;
use Core\Config;
use Core\Helper;
use Core\Request;
use Core\Response;
use Core\Response\JSONResponse;

class ResetPasswordController
{
    public function preparationResetPassword(Request $request): Response
    {
        if (isset($_SESSION['id'])) {
            return new JSONResponse(Helper::showError('Вошедший пользователь не может сбросить пароль'), 403);
        }

        $email = null;

        if (!isset($request->getData()['GET']['email'])) {
            return new JSONResponse(Helper::showError('Не передан email'), 400);
        }

        $email = trim($request->getData()['GET']['email']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JSONResponse(Helper::showError('Email не корректен'), 400);
        }

        $url = $request->getData()['originUrl'] . $request->getRoute();

        return App::getService('resetPasswordService')->createdResetPasswordAndSendEmail($email, $url, Config::getConfig('resetPassword.expiresInMinutes'));
    }

    public function resetPassword(Request $request): Response
    {
        if (isset($_SESSION['id'])) {
            return new JSONResponse(Helper::showError('Вошедший пользователь не может сбросить пароль'), 403);
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
            return new JSONResponse(Helper::showError(implode(', ', $errors)), 400);
        }

        $id = (int) trim($request->getData()['GET']['id']);
        $token = trim($request->getData()['GET']['token']);
        $password = trim($request->getData()['PUT']['password']);

        return App::getService('resetPasswordService')->resetPassword($id, $token, $password);
    }
}
