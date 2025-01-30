<?php

namespace Controllers;

use Core\App;
use Core\Request;
use Core\Response;
use Core\Response\JSONResponse;

class AdminController
{
    public function list(): Response
    {
        return !$this->isAdmin() ? $this->accessForbidden() : new JSONResponse(App::getService('adminService')->getUsersList());
    }

    public function get(array $params): Response
    {
        if (!$this->isAdmin()) {
            return  $this->accessForbidden();
        }

        $data = App::getService('adminService')->getUserById($params[0]);

        if ($data === null) {
            return $this->pageNotFound();
        }

        return new JSONResponse($data);
    }

    public function delete(array $params): Response
    {
        if (!$this->isAdmin()) {
            return  $this->accessForbidden();
        }

        App::getService('adminService')->deleteUserById($params[0]);

        return new JSONResponse();
    }

    public function update(array $params, Request $request): Response
    {
        if (!$this->isAdmin()) {
            return  $this->accessForbidden();
        }

        $id = $params[0];

        $data = App::getService('adminService')->getUserById($id);

        if ($data === null) {
            return $this->pageNotFound();
        }

        return App::getService('userService')->updateUser($request->getData()['PUT'], (int) $id, $_SESSION['role']);
    }

    private function isAdmin(): bool
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    private function accessForbidden(): Response
    {
        return new Response('html', 'Доступ запрещён', 403);
    }

    private function pageNotFound(): Response
    {
        return new Response('html', 'Страница не найдена', 404);
    }
}
