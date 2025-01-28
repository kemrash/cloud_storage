<?php

namespace Services;

use Core\App;
use Core\Config;
use Core\ErrorApp;
use Core\Response;
use DateTime;
use Exception;
use Models\ResetPassword;

class ResetPasswordService
{
    public function createdResetPasswordAndSendEmail(string $email, string $url, int $expiresInMinutes = 30): Response
    {
        $dateTime = new DateTime();
        $createdAt = $dateTime->format(Config::getConfig('app.dateTimeFormat'));

        try {
            App::getService('resetPasswordRepository')::clearOldResetPassword($createdAt);

            $user = App::getService('userRepository')::getUserBy(['email' => $email]);

            if ($user === null) {
                return new Response('json', json_encode(ErrorApp::showError("Не найден пользователь с email = {$email}")), 404);
            }

            $resetPassword = App::getService('resetPasswordRepository')::getResetPasswordBy(['userId' => $user->id]);

            if ($resetPassword !== null && DateTime::createFromFormat(Config::getConfig('app.dateTimeFormat'), $resetPassword->expiresAt) >= $dateTime) {
                return new Response('json', json_encode(ErrorApp::showError('Ещё не прошло ' . $expiresInMinutes . ' минут с момента последнего запроса')), 400);
            }

            $expiresAt = $dateTime->modify("+{$expiresInMinutes} minutes")->format(Config::getConfig('app.dateTimeFormat'));

            $token = bin2hex(random_bytes(32));
            $hashedToken = password_hash($token, PASSWORD_DEFAULT);
            $resetPassword = new ResetPassword($user->id, $hashedToken, $expiresAt, $createdAt);

            App::getService('resetPasswordRepository')::createdResetPassword($resetPassword);

            $resetPassword->sendEmail($user->email, $url, $token);

            return new Response('json', json_encode(['status' => 'ok']));
        } catch (Exception $e) {
            ErrorApp::writeLog(self::class . ': ' . $e->getMessage());

            return new Response('json', json_encode(ErrorApp::showError('Произошла ошибка сервера')), 500);
        }
    }
}
