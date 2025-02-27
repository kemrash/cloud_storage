<?php

namespace Controllers;

use Core\App;
use Core\Config;
use Core\Helper;
use Core\Request;
use Core\Response;
use Models\User;
use traits\StatusResponseTrait;
use traits\UserTrait;

class AdminController
{
    use UserTrait;
    use StatusResponseTrait;

    /**
     * Возвращает список пользователей в формате JSON.
     *
     * @return Response JSON-ответ со списком пользователей или ответ с ошибкой доступа.
     */
    public function list(): Response
    {
        if (!$this->isAdmin()) {
            return $this->accessForbidden();
        }

        $data = App::getService('user')->list('id', 'email', 'role', 'age', 'gender');

        return new Response('json', $data);
    }

    /**
     * Создает нового пользователя.
     *
     * @param Request $request HTTP запрос, содержащий данные для создания пользователя.
     * 
     * @return Response Ответ с результатом создания пользователя.
     * 
     * Метод проверяет, является ли текущий пользователь администратором и содержит ли запрос данные в POST.
     * Если проверка не пройдена, возвращается ответ с ошибкой доступа.
     * Затем данные из запроса валидируются, и в случае наличия ошибок возвращается ответ с ошибками валидации.
     * Если пользователь с таким email уже существует, возвращается соответствующая ошибка.
     * В случае успешного создания пользователя возвращается ответ с данными нового пользователя.
     */
    public function create(Request $request): Response
    {
        if (!$this->isAdmin() || !isset($request->getData()['POST'])) {
            return  $this->accessForbidden();
        }

        $data = $request->getData()['POST'];

        $user = new User();
        $errors = $user->allValidation($data, true);

        if (count($errors) > 0) {
            return new Response('json', Helper::showError(implode(' ', $errors)), 400);
        }

        $result = $user->create();

        if (isset($result['code']) && $result['code'] === '23000') {
            return new Response('json', Helper::showError('Пользователь с таким email уже существует'), 400);
        }

        return new Response('json', $result);
    }

    /**
     * Получает информацию о пользователе по его идентификатору.
     *
     * @param array<int, string> $params Массив параметров, где первый элемент - идентификатор пользователя.
     * @return Response Ответ с данными пользователя в формате JSON или сообщение об ошибке.
     */
    public function get(array $params): Response
    {
        if (!$this->isAdmin()) {
            return  $this->accessForbidden();
        }

        $userId = $params[0];

        if (!ctype_digit($userId)) {
            return $this->pageNotFound();
        }

        $userId = (int) $userId;
        $user = new User();

        if (!$user->get(['id' => $userId])) {
            return $this->pageNotFound();
        }

        return new Response(
            'json',
            [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'age' => $user->age,
                'gender' => $user->gender
            ]
        );
    }

    public function delete(array $params): Response
    {
        if (!$this->isAdmin()) {
            return  $this->accessForbidden();
        }

        $userId = $params[0];

        if (!ctype_digit($userId)) {
            return $this->pageNotFound();
        }

        $userId = (int) $userId;

        if ($userId === Config::getConfig('app.idUserSystem')) {
            return new Response('json', Helper::showError('Нельзя удалять системного пользователя'));
        }

        $user = new User();
        $user->delete($userId);
        // return App::getService('adminService')->deleteUserById($params[0]);

        return new Response();
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

        $data = $request->getData()['PUT'];
        $userId = $params[0];

        if (!ctype_digit($userId)) {
            return $this->pageNotFound();
        }

        $userId = (int) $userId;

        $user = new User();

        if (!$user->get(['id' => $userId])) {
            return $this->pageNotFound();
        }

        $errors = $user->allValidation($data, true);

        if (count($errors) > 0) {
            return new Response('json', Helper::showError(implode(' ', $errors)), 400);
        }

        $result = $user->update();

        if (isset($result['code']) && $result['code'] === '23000') {
            return new Response('json', Helper::showError('Пользователь с таким email уже существует'), 400);
        }

        return new Response();
    }
}
