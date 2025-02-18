<?php

namespace Models;

use Core\AppException;
use Core\Helper;
use Core\Config;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mail
{
    /**
     * Отправляет электронное письмо на указанный адрес.
     *
     * @param string $address Адрес электронной почты получателя.
     * @param string $title Тема письма.
     * @param string $message Текст сообщения.
     *
     * @throws AppException В случае ошибки при отправке письма.
     */
    public function sendEmail(string $address, string $title, string $message): void
    {
        $mail = new PHPMailer();
        $connectionMailSMTP = Config::getConfig('mailSMTP');

        try {
            $mail->isSMTP();
            $mail->Host = $connectionMailSMTP['host'];
            $mail->SMTPAuth = $connectionMailSMTP['SMTPAuth'];
            $mail->Username = $connectionMailSMTP['user'];
            $mail->Password = $connectionMailSMTP['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $connectionMailSMTP['port'];

            $mail->CharSet = 'UTF-8';
            $mail->setLanguage('ru', __DIR__ . '/vendor/phpmailer/phpmailer/language/');

            $mail->setFrom($connectionMailSMTP['from'], Config::getConfig('app.name'));
            $mail->addAddress($address);
            $mail->isHTML(false);
            $mail->Subject = $title;
            $mail->Body = $message;

            if (!$mail->send()) {
                Helper::writeLog(self::class . ': ' . $mail->ErrorInfo);
            }
        } catch (Exception $e) {
            throw new AppException(__CLASS__, $e->getMessage());
        }
    }
}
