<?php

namespace Controllers;

use Core\App;
use Core\Config;
use Core\ErrorApp;
use Core\Request;
use Core\Response;

class ResetPasswordController
{
    public function preparationResetPassword(Request $request): Response
    {
        if (!isset($request->getData()['GET']['email']) || !filter_var(trim($request->getData()['GET']['email']), FILTER_VALIDATE_EMAIL)) {
            return new Response('json', json_encode(ErrorApp::showError('Не передан email, или его значение не корректно')), 400);
        }

        App::getService('session')->startSession();

        if (isset($_SESSION['id'])) {
            return new Response('json', json_encode(ErrorApp::showError('Вошедший пользователь не может сбросить пароль')), 403);
        }

        $url = $request->getData()['originUrl'] . $request->getRoute();

        return App::getService('resetPasswordService')->createdResetPasswordAndSendEmail(trim($request->getData()['GET']['email']), $url, Config::getConfig('resetPassword.expiresInMinutes'));
    }
}
