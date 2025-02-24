<?php

namespace Controllers;

use Core\App;
use Core\Request;
use Core\Response;

class AdminController
{
    /**
     * Возвращает список пользователей в формате JSON.
     *
     * @return Response JSON-ответ со списком пользователей или ответ с ошибкой доступа.
     */
    public function list(): Response
    {
        return !$this->isAdmin() ? $this->accessForbidden() : new Response('json', App::getService('adminService')->getUsersList());
    }

    /**
     * Создает нового пользователя.
     *
     * Этот метод проверяет, является ли текущий пользователь администратором.
     * Если пользователь не администратор, возвращается ответ с запретом доступа.
     * Если пользователь администратор, вызывается сервис для создания нового пользователя.
     *
     * @param Request $request Объект запроса, содержащий данные POST.
     * @return Response Ответ с результатом создания пользователя или запретом доступа.
     */
    public function create(Request $request): Response
    {
        if (!$this->isAdmin()) {
            return  $this->accessForbidden();
        }

        return App::getService('adminService')->createUser($request->getData()['POST']);
    }

    /**
     * Возвращает данные пользователя по его ID.
     *
     * Этот метод проверяет, является ли текущий пользователь администратором.
     * Если пользователь не администратор, возвращается ответ с запретом доступа.
     * Если пользователь администратор, вызывается сервис для получения данных пользователя по ID.
     * Если пользователь с указанным ID не найден, возвращается ответ с ошибкой "Страница не найдена".
     *
     * @param array<string> $params Массив параметров, содержащий ID пользователя.
     * @return Response JSON-ответ с данными пользователя или ответ с ошибкой доступа/страницы не найдена.
     */
    public function get(array $params): Response
    {
        if (!$this->isAdmin()) {
            return  $this->accessForbidden();
        }

        $data = App::getService('adminService')->getUserById($params[0]);

        if ($data === null) {
            return $this->pageNotFound();
        }

        return new Response('json', $data);
    }

    /**
     * Удаляет пользователя по его идентификатору.
     *
     * @param array<string> $params Массив параметров, где первый элемент - идентификатор пользователя.
     * @return Response Ответ после выполнения операции удаления.
     */
    public function delete(array $params): Response
    {
        if (!$this->isAdmin()) {
            return  $this->accessForbidden();
        }

        return App::getService('adminService')->deleteUserById($params[0]);
    }

    /**
     * Обновляет данные пользователя.
     *
     * @param array<string> $params Параметры запроса, где первый элемент - ID пользователя.
     * @param Request $request Объект запроса, содержащий данные для обновления.
     * @return Response Ответ с результатом операции.
     */
    public function update(array $params, Request $request): Response
    {
        if (!$this->isAdmin()) {
            return  $this->accessForbidden();
        }

        $id = $params[0];

        return App::getService('userService')->updateUser($request->getData()['PUT'], (int) $id, $_SESSION['role']);
    }

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
     * Метод для возврата ответа о запрете доступа.
     *
     * @return Response Ответ с информацией о запрете доступа.
     */
    private function accessForbidden(): Response
    {
        return new Response('renderError', 'Доступ запрещен', 403);
    }

    /**
     * Возвращает ответ с ошибкой "Страница не найдена".
     *
     * @return Response Ответ с ошибкой "Страница не найдена".
     */
    private function pageNotFound(): Response
    {
        return new Response('renderError', 'Страница не найдена', 404);
    }
}
