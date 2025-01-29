<?php

namespace Services;

use Core\App;
use Core\Config;
use Core\Helper;
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
                return new Response('json', json_encode(Helper::showError("Не найден пользователь с email = {$email}")), 404);
            }

            $resetPassword = App::getService('resetPasswordRepository')::getResetPasswordBy(['userId' => $user->id]);

            if ($resetPassword !== null && DateTime::createFromFormat(Config::getConfig('app.dateTimeFormat'), $resetPassword->expiresAt) >= $dateTime) {
                return new Response('json', json_encode(Helper::showError('Ещё не прошло ' . $expiresInMinutes . ' минут с момента последнего запроса')), 400);
            }

            $expiresAt = $dateTime->modify("+{$expiresInMinutes} minutes")->format(Config::getConfig('app.dateTimeFormat'));

            $token = bin2hex(random_bytes(32));
            $hashedToken = password_hash($token, PASSWORD_DEFAULT);
            $resetPassword = new ResetPassword($user->id, $hashedToken, $expiresAt, $createdAt);

            App::getService('resetPasswordRepository')::createdResetPassword($resetPassword);

            $resetPassword->sendEmail($user->email, $url, $token);

            return new Response('json', json_encode(['status' => 'ok']));
        } catch (Exception $e) {
            Helper::writeLog(self::class . ': ' . $e->getMessage());

            return new Response('json', json_encode(Helper::showError('Произошла ошибка сервера')), 500);
        }
    }

    public function resetPassword(int $id, string $token, string $password): Response
    {
        $dateTime = new DateTime();
        $currentDate = $dateTime->format(Config::getConfig('app.dateTimeFormat'));

        try {
            App::getService('resetPasswordRepository')::clearOldResetPassword($currentDate);

            $resetPassword = App::getService('resetPasswordRepository')::getResetPasswordBy(['userId' => $id]);

            if ($resetPassword === null) {
                return new Response('json', json_encode(Helper::showError("Не найден пользователь с id = {$id} или токен истек")), 404);
            }

            if (!$resetPassword->isValidToken($token)) {
                return new Response('json', json_encode(Helper::showError('Неверный токен')), 400);
            }

            $passwordEncrypted = password_hash($password, PASSWORD_DEFAULT);

            $data = App::getService('resetPasswordRepository')::transactionUpdatePasswordUserAndDeleteResetPassword($id, $passwordEncrypted);

            if ($data['status'] === 'error') {
                return new Response('json', json_encode(Helper::showError($data['data'])), 400);
            }

            return new Response('json', json_encode(['status' => 'ok']));
        } catch (Exception $e) {
            Helper::writeLog(self::class . ': ' . $e->getMessage());

            return new Response('json', json_encode(Helper::showError('Произошла ошибка сервера')), 500);
        }
    }
}
