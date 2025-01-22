<?php

namespace Models;

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
