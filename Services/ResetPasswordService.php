<?php

namespace Services;

use Core\App;
use Core\Config;
use Core\Helper;
use Core\Response;
use Core\Response\JSONResponse;
use DateTime;
use Models\ResetPassword;

class ResetPasswordService
{
    /**
     * Создает запись для сброса пароля и отправляет письмо с инструкциями.
     *
     * @param string $email Электронная почта пользователя, для которого создается запись сброса пароля.
     * @param string $url URL для сброса пароля, который будет отправлен пользователю.
     * @param int $expiresInMinutes Время в минутах, через которое ссылка для сброса пароля истечет. По умолчанию 30 минут.
     * @return Response JSON-ответ с результатом операции.
     */
    public function createdResetPasswordAndSendEmail(string $email, string $url, int $expiresInMinutes = 30): Response
    {
        $dateTime = new DateTime();
        $createdAt = $dateTime->format(Config::getConfig('app.dateTimeFormat'));

        App::getService('resetPasswordRepository')::clearOldResetPassword($createdAt);

        $user = App::getService('userRepository')::getUserBy(['email' => $email]);

        if ($user === null) {
            return new JSONResponse(Helper::showError("Не найден пользователь с email = {$email}"), 404);
        }

        $resetPassword = App::getService('resetPasswordRepository')::getResetPasswordBy(['userId' => $user->id]);

        if ($resetPassword !== null && DateTime::createFromFormat(Config::getConfig('app.dateTimeFormat'), $resetPassword->expiresAt) >= $dateTime) {
            return new JSONResponse(Helper::showError('Ещё не прошло ' . $expiresInMinutes . ' минут с момента последнего запроса'), 400);
        }

        $expiresAt = $dateTime->modify("+{$expiresInMinutes} minutes")->format(Config::getConfig('app.dateTimeFormat'));

        $token = bin2hex(random_bytes(32));
        $hashedToken = password_hash($token, PASSWORD_DEFAULT);
        $resetPassword = new ResetPassword($user->id, $hashedToken, $expiresAt, $createdAt);

        App::getService('resetPasswordRepository')::createdResetPassword($resetPassword);

        $resetPassword->sendEmail($user->email, $url, $token);

        return new JSONResponse();
    }

    /**
     * Сбрасывает пароль пользователя.
     *
     * @param int $id Идентификатор пользователя.
     * @param string $token Токен для сброса пароля.
     * @param string $password Новый пароль пользователя.
     * @return Response Ответ в формате JSON.
     */
    public function resetPassword(int $id, string $token, string $password): Response
    {
        $dateTime = new DateTime();
        $currentDate = $dateTime->format(Config::getConfig('app.dateTimeFormat'));

        App::getService('resetPasswordRepository')::clearOldResetPassword($currentDate);

        $resetPassword = App::getService('resetPasswordRepository')::getResetPasswordBy(['userId' => $id]);

        if ($resetPassword === null) {
            return new JSONResponse(Helper::showError("Не найден пользователь с id = {$id} или токен истек"), 404);
        }

        if (!$resetPassword->isValidToken($token)) {
            return new JSONResponse(Helper::showError('Неверный токен'), 400);
        }

        $passwordEncrypted = password_hash($password, PASSWORD_DEFAULT);

        $data = App::getService('resetPasswordRepository')::transactionUpdatePasswordUserAndDeleteResetPassword($id, $passwordEncrypted);

        if ($data['status'] === 'error') {
            return new JSONResponse(Helper::showError($data['data']), 400);
        }

        return new JSONResponse();
    }
}
