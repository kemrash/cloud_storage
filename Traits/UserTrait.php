<?php

namespace Traits;

trait UserTrait
{
    /**
     * Проверяет, является ли текущий пользователь администратором.
     *
     * @return bool Возвращает true, если пользователь является администратором, иначе false.
     */
    private function isAdmin(): bool
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    /**
     * Проверяет, выполнен ли вход пользователя.
     *
     * Метод проверяет, установлена ли сессия пользователя по наличию ключа 'id' в массиве $_SESSION.
     *
     * @return bool Возвращает true, если пользователь авторизован, иначе false.
     */
    private function isLogin(): bool
    {
        return isset($_SESSION['id']);
    }
}
