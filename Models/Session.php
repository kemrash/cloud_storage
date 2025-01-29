<?php

namespace Models;

use Core\App;
use Core\ErrorApp;
use Exception;

class Session
{
    private const SESSION_NAME = 'cloud_storage';

    public function startSession(): void
    {
        session_name(self::SESSION_NAME);
        session_set_cookie_params([
            'httponly' => true,
        ]);
        session_start();

        if (isset($_SESSION['id'])) {
            try {
                $user = App::getService('userRepository')::getUserBy(['id' => (int) $_SESSION['id']]);

                if ($user !== null) {
                    $_SESSION['id'] = $user->id;
                    $_SESSION['role'] = $user->role;
                } else {
                    $_SESSION['id'] = '';
                    $_SESSION['role'] = '';
                }
            } catch (Exception $e) {
                ErrorApp::writeLog(self::class . ': ' . $e->getMessage());

                throw new Exception($e->getMessage());
            }
        }
    }

    public function destroySession(): void
    {
        session_name(self::SESSION_NAME);
        session_start();

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }
}
