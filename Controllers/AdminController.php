<?php

namespace Controllers;

use Core\App;
use Core\Request;
use Core\Response;
use Core\Response\AccessDeniedResponse;
use Core\Response\JSONResponse;
use Core\Response\PageNotFoundResponse;

class AdminController
{
    public function list(): Response
    {
        return !$this->isAdmin() ? $this->accessForbidden() : new JSONResponse(App::getService('adminService')->getUsersList());
    }

    public function create(Request $request): Response
    {
        if (!$this->isAdmin()) {
            return  $this->accessForbidden();
        }

        return App::getService('adminService')->createUser($request->getData()['POST']);
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

        return App::getService('adminService')->deleteUserById($params[0]);
    }

    public function update(array $params, Request $request): Response
    {
        if (!$this->isAdmin()) {
            return  $this->accessForbidden();
        }

        $id = $params[0];

        return App::getService('userService')->updateUser($request->getData()['PUT'], (int) $id, $_SESSION['role']);
    }

    private function isAdmin(): bool
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    private function accessForbidden(): Response
    {
        return new AccessDeniedResponse();
    }

    private function pageNotFound(): Response
    {
        return new PageNotFoundResponse();
    }
}
