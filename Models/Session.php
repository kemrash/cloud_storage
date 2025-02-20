<?php

namespace Models;

use Core\App;

class Session
{
    private const SESSION_NAME = 'cloud_storage';

    /**
     * Запускает сессию и устанавливает параметры cookie.
     * 
     * Если в сессии уже существует идентификатор пользователя, метод пытается получить
     * пользователя из репозитория по этому идентификатору. Если пользователь не найден,
     * сессия уничтожается. В противном случае, идентификатор и роль пользователя обновляются
     * в сессии.
     * 
     * @return void
     */
    public function startSession(): void
    {
        session_name(self::SESSION_NAME);
        session_set_cookie_params([
            'httponly' => true,
            'domain' => 'localhost',
        ]);
        session_start();

        if (isset($_SESSION['id'])) {
            $user = App::getService('userRepository')::getUserBy(['id' => (int) $_SESSION['id']]);

            if ($user === null) {
                $this->destroySession();
                return;
            }

            $_SESSION['id'] = $user->id;
            $_SESSION['role'] = $user->role;
        }
    }

    /**
     * Уничтожает текущую сессию.
     *
     * Этот метод очищает массив $_SESSION, удаляет сессионные куки, если они используются,
     * и завершает сессию с помощью session_destroy().
     *
     * @return void
     */
    public function destroySession(): void
    {
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
