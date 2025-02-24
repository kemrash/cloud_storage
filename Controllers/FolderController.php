<?php

namespace Controllers;

use Core\App;
use Core\Helper;
use Core\Request;
use Core\Response;
use Core\Response\JSONResponse;
use traits\PageTrait;
use traits\UserTrait;

class FolderController
{
    use UserTrait;
    use PageTrait;

    /**
     * Возвращает список папок пользователя.
     *
     * @return Response Ответ с данными о папках пользователя.
     */
    public function list(): Response
    {
        if ($response = $this->checkUserAuthorization()) {
            return $response;
        }

        return App::getService('folderService')->getUserFoldersList((int) $_SESSION['id']);
    }

    /**
     * Добавляет новую папку для пользователя.
     *
     * @param Request $request HTTP-запрос, содержащий данные для создания папки.
     * @return Response HTTP-ответ, содержащий результат операции.
     */
    public function add(Request $request): Response
    {
        if ($response = $this->checkUserAuthorization()) {
            return $response;
        }

        $validated = $this->getValidatedData($request, 'POST', 'parentId');

        if ($validated instanceof Response) {
            return $validated;
        }

        [$userId, $parentId, $name] = $validated;

        return App::getService('folderService')->createUserFolder($userId, $parentId, $name);
    }

    /**
     * Переименовывает папку пользователя.
     *
     * @param Request $request HTTP-запрос, содержащий данные для переименования папки.
     * @return Response HTTP-ответ с результатом операции.
     */
    public function rename(Request $request): Response
    {
        if ($response = $this->checkUserAuthorization()) {
            return $response;
        }

        $validated = $this->getValidatedData($request, 'PUT', 'id');

        if ($validated instanceof Response) {
            return $validated;
        }

        [$userId, $id, $name] = $validated;

        return App::getService('folderService')->renameUserFolder($userId, $id, $name);
    }

    /**
     * Получает информацию о папке пользователя.
     *
     * @param array<string> $params Массив параметров, где первый элемент - идентификатор папки.
     * @return Response Ответ с информацией о папке или сообщение об ошибке.
     */
    public function get(array $params): Response
    {
        if ($response = $this->checkUserAuthorization()) {
            return $response;
        }

        if ($response = $this->checkNotFoundPage($params[0])) {
            return $response;
        }

        $userId = (int) $_SESSION['id'];
        $folderId = (int) $params[0];

        return App::getService('folderService')->getUserFolder($userId, $folderId);
    }

    /**
     * Удаляет папку пользователя.
     *
     * @param array<string> $params Массив параметров, где первый элемент - ID папки.
     * @return Response Ответ сервера.
     */
    public function remove(array $params): Response
    {
        if ($response = $this->checkUserAuthorization()) {
            return $response;
        }

        if ($response = $this->checkNotFoundPage($params[0])) {
            return $response;
        }

        $userId = (int) $_SESSION['id'];
        $folderId = (int) $params[0];

        return App::getService('folderService')->removeUserFolder($userId, $folderId);
    }

    /**
     * Валидирует данные запроса и возвращает массив с данными или объект ответа с ошибкой.
     *
     * @param Request $request Объект запроса.
     * @param string $httpMethod HTTP метод запроса (GET, POST и т.д.).
     * @param string $idKey Ключ идентификатора в данных запроса.
     * 
     * @return array{int, int, string}|Response Возвращает массив с userId, id и name или объект ответа с ошибкой.
     */
    private function getValidatedData(Request $request, string $httpMethod, string $idKey): array|Response
    {
        $data = $request->getData()[strtoupper($httpMethod)] ?? [];

        if (!isset($data[$idKey]) || !ctype_digit((string)$data[$idKey])) {
            return new JSONResponse(
                Helper::showError("Не передан {$idKey} или его значение не корректно"),
                400
            );
        }

        if (!isset($data['name']) || !is_string($data['name']) || mb_strlen($data['name']) > 255) {
            return new JSONResponse(
                Helper::showError('Не передан name или его значение превышает 255 символов'),
                400
            );
        }

        $userId = (int) $_SESSION['id'];
        $id = (int) $data[$idKey];
        $name = $data['name'];

        return [$userId, $id, $name];
    }
}
