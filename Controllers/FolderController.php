<?php

namespace Controllers;

use Core\App;
use Core\Helper;
use Core\Request;
use Core\Response;
use traits\StatusResponseTrait;
use traits\UserTrait;

class FolderController
{
    use UserTrait;
    use StatusResponseTrait;

    /**
     * Возвращает список папок пользователя.
     *
     * @return Response Ответ с данными о папках пользователя.
     */
    public function list(): Response
    {
        if (!$this->isLogin()) {
            return $this->accessForbidden();
        }

        $data = App::getService('folder')->list((int) $_SESSION['id']);

        return new Response('json', $data);
    }

    /**
     * Добавляет новую папку для пользователя.
     *
     * @param Request $request HTTP-запрос, содержащий данные для создания папки.
     * @return Response HTTP-ответ, содержащий результат операции.
     */
    public function add(Request $request): Response
    {
        if (!$this->isLogin()) {
            return $this->accessForbidden();
        }

        $validated = $this->getValidatedData($request, 'POST', 'parentId');

        if ($validated instanceof Response) {
            return $validated;
        }

        [$userId, $parentId, $name] = $validated;

        if ($parentId === 0) {
            return new Response('json', Helper::showError('Нельзя создать еще одну корневую папку'), 400);
        }

        $folder = App::getService('folder');

        if (!$folder->get($userId, $parentId)) {
            return $this->pageNotFound();
        }

        if (!$folder->create($userId, $parentId, $name)) {
            return new Response('json', Helper::showError('Папка с такими параметрами уже существует'), 400);
        }

        return new Response();
    }

    /**
     * Переименовывает папку пользователя.
     *
     * @param Request $request HTTP-запрос, содержащий данные для переименования папки.
     * @return Response HTTP-ответ, указывающий на результат операции.
     *
     * Метод проверяет, авторизован ли пользователь, валидирует входные данные и переименовывает папку.
     * Если пользователь не авторизован, возвращается ответ с ошибкой доступа.
     * Если входные данные не прошли валидацию, возвращается ответ с ошибкой валидации.
     * Если папка не найдена, возвращается ответ с ошибкой "страница не найдена".
     * Если папка с таким именем уже существует, возвращается ответ с ошибкой.
     * В случае успешного переименования возвращается status ok.
     */
    public function rename(Request $request): Response
    {
        if (!$this->isLogin()) {
            return $this->accessForbidden();
        }

        $validated = $this->getValidatedData($request, 'PUT', 'id');

        if ($validated instanceof Response) {
            return $validated;
        }

        [$userId, $id, $name] = $validated;

        $folder = App::getService('folder');

        if (!$folder->get($userId, $id)) {
            return $this->pageNotFound();
        }

        if (!$folder->rename($name)) {
            return new Response('json', Helper::showError('Папка с таким названием уже существует в родительской папке'), 400);
        }

        return new Response();
    }

    /**
     * Получает информацию о папке и её содержимом.
     *
     * @param array<int, string> $params Массив параметров, где первый элемент - ID папки.
     * @return Response Ответ в формате JSON с информацией о папке и её содержимом.
     */
    public function get(array $params): Response
    {
        if (!$this->isLogin()) {
            return $this->accessForbidden();
        }

        if (!isset($params[0]) || !ctype_digit($params[0])) {
            return $this->pageNotFound();
        }

        $userId = (int) $_SESSION['id'];
        $folderId = (int) $params[0];

        $folder = App::getService('folder');

        if (!$folder->get($userId, $folderId)) {
            return $this->pageNotFound();
        }

        $files = App::getService('file')->getFilesInFolder($folderId);

        $result = [
            'id' => $folderId,
            'userId' => $userId,
            'parentId' => $folder->parentId,
            'name' => $folder->name,
            'content' => [
                'folders' => $folder->getFoldersInFolder(),
                'files' => $files,
            ],
        ];

        return new Response('json', $result);
    }

    /**
     * Удаляет папку пользователя.
     *
     * @param array<string> $params Массив параметров, где первый элемент - ID папки.
     * @return Response Ответ сервера.
     */
    public function remove(array $params): Response
    {
        if (!$this->isLogin()) {
            return $this->accessForbidden();
        }

        if (!isset($params[0]) || !ctype_digit($params[0])) {
            return $this->pageNotFound();
        }

        $userId = (int) $_SESSION['id'];
        $folderId = (int) $params[0];

        $folder = App::getService('folder');

        if (!$folder->get($userId, $folderId)) {
            return $this->pageNotFound();
        }

        if ($folder->parentId === 0) {
            return new Response('json', Helper::showError('Нельзя удалить корневую папку'), 400);
        }

        $folder->delete();

        return new Response();
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
            return new Response(
                'json',
                Helper::showError("Не передан {$idKey} или его значение не корректно"),
                400
            );
        }

        if (!isset($data['name']) || !is_string($data['name']) || mb_strlen($data['name']) > 255) {
            return new Response(
                'json',
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
