<?php

namespace Models;

use Core\ErrorApp;
use Core\Config;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mail
{
    public function sendMail(string $address, string $title, string $message): void
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
                ErrorApp::writeLog(get_class($this) . ': ' . $mail->ErrorInfo);
            }
        } catch (Exception $e) {
            ErrorApp::writeLog(get_class($this) . ': ' . $e->getMessage());
        }
    }
}
