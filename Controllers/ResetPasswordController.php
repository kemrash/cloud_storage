<?php

namespace Controllers;

use Core\App;
use Core\Config;
use Core\ErrorApp;
use Core\Request;
use Core\Response;

class ResetPasswordController
{
    public function preparationResetPassword(Request $request)
    {
        if (!isset($request->getData()['GET']['email']) || !filter_var($request->getData()['GET']['email'], FILTER_VALIDATE_EMAIL)) {
            return new Response('json', json_encode(ErrorApp::showError('Не передан email, или его значение не корректно')), 400);
        }

        return App::getService('resetPasswordService')->createdResetPasswordAndSendEmail($request->getData()['GET']['email'], Config::getConfig('resetPassword.expiresInMinutes'));
    }
}
