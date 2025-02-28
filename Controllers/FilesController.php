<?php

namespace Controllers;

use Core\App;
use Core\Config;
use Core\Helper;
use Core\Request;
use Core\Response;
use Flow\Request as FlowRequest;
use Models\User;
use traits\StatusResponseTrait;
use traits\UserTrait;

class FilesController
{
    use UserTrait;
    use StatusResponseTrait;

    /**
     * Возвращает список файлов пользователя.
     *
     * @return Response Ответ с авторизацией пользователя или список файлов.
     */
    public function list(): Response
    {
        if (!$this->isLogin()) {
            return $this->accessForbidden();
        }

        $data = App::getService('file')->list((int) $_SESSION['id']);

        return new Response('json', $data);
    }

    /**
     * Получает информацию о файле по его идентификатору.
     *
     * @param array{0: string} $params Массив параметров, где первый элемент - идентификатор файла.
     * @return Response Ответ с информацией о файле в формате JSON.
     */
    public function getFile(array $params): Response
    {
        if (!$this->isLogin()) {
            return $this->accessForbidden();
        }

        if (!isset($params[0]) || !ctype_digit($params[0])) {
            return $this->pageNotFound();
        }

        $userId = (int) $_SESSION['id'];
        $fileId = (int) $params[0];
        $file = App::getService('file');

        if (!$file->get(['id' => $fileId])) {
            return $this->pageNotFound();
        }

        $fileShareUsersList = App::getService('share')::getUsers($file->id);

        if ($file->userId !== $userId && !in_array(['userId' => $userId], $fileShareUsersList)) {
            return $this->accessForbidden();
        }

        return new Response(
            'json',
            [
                'id' => $file->id,
                'userId' => $file->userId,
                'serverName' => $file->serverName,
                'folderId' => $file->folderId,
                'origenName' => $file->origenName,
                'mimeType' => $file->mimeType,
                'size' => $file->size,
            ]
        );
    }

    /**
     * Добавляет файл в облачное хранилище.
     *
     * @param Request $request Объект запроса, содержащий данные о загружаемом файле.
     * @return Response Ответ с результатом операции.
     *
     * @throws Exception Если произошла ошибка при добавлении файла.
     *
     * Проверяет, авторизован ли пользователь, и если нет, возвращает ответ с ошибкой доступа.
     * Проверяет наличие файла в запросе и его корректность.
     * Проверяет наличие и корректность параметра folderId.
     * Проверяет размер файла на превышение максимального допустимого размера.
     * Проверяет корректность названия файла.
     * Проверяет существование папки и файла с таким же именем в этой папке.
     * Добавляет файл по частям и возвращает результат операции.
     */
    public function add(Request $request): Response
    {
        if (!$this->isLogin()) {
            return $this->accessForbidden();
        }

        $userId = (int) $_SESSION['id'];

        if (!isset($request->getData()['FILES']['file'])) {
            return new Response('json', Helper::showError('Файл не передан'), 400);
        }

        if ($request->getData()['FILES']['file']['error'] !== 0) {
            return new Response('json', Helper::showError('Файл не загружен'), 400);
        }

        $flowRequest = new FlowRequest();
        $folderId = $flowRequest->getParam('folderId');

        if (!isset($folderId) || !ctype_digit($folderId)) {
            return new Response('json', Helper::showError('Не передан folderId или его значение не корректно'), 400);
        }

        $folderId = (int) $folderId;

        $maxFileSize = Config::getConfig('app.uploadFile.maxFileSize');
        $finalFileSize = $flowRequest->getParam('finalSize');

        if ($finalFileSize > $maxFileSize) {
            return new Response('json', Helper::showError('Превышен максимальный размер в ' . $maxFileSize . ' байт'), 400);
        }

        $fileName = $flowRequest->getFileName();

        if ($fileName === null) {
            return new Response('json', Helper::showError('Название файла не передано, для загрузки файлов необходимо использовать скрипт flow.js'), 400);
        }

        if (mb_strlen($fileName) > 255) {
            return new Response('json', Helper::showError('Название файла, вместе с расширением, не должно превышать 255 символов'), 400);
        }

        $folder = App::getService('folder');

        if (!$folder->get($userId, $folderId)) {
            return new Response('json', Helper::showError('Папка не найдена'), 400);
        }

        $file = App::getService('file');

        if ($file->get(['folderId' => $folderId, 'origenName' => $fileName])) {
            return new Response('json', Helper::showError('Файл с таким именем уже существует в этой папке'), 400);
        }

        $data = $file->addFileChunks($userId, $folderId, $flowRequest);

        return new Response('json', $data);
    }

    /**
     * Переименовывает файл пользователя.
     *
     * @param Request $request Объект запроса, содержащий данные для переименования файла.
     * @return Response Ответ с результатом операции.
     *
     * Данные запроса должны содержать следующие параметры:
     * - id (string): Идентификатор файла, должен быть числом.
     * - fileName (string): Новое имя файла, строка, не превышающая 255 символов.
     *
     * Возвращает JSON-ответ с ошибкой в следующих случаях:
     * - Пользователь не авторизован.
     * - Некорректные данные запроса.
     * - Имя файла превышает 255 символов.
     * - Файл не найден.
     * - Имя файла уже существует в данной папке.
     */
    public function rename(Request $request): Response
    {
        if (!$this->isLogin()) {
            return $this->accessForbidden();
        }

        $userId = (int) $_SESSION['id'];

        $data = $request->getData()['PUT'];

        if (
            !isset($data['id']) ||
            !ctype_digit($data['id']) ||
            !isset($data['fileName']) ||
            !is_string($data['fileName'])
        ) {
            return new Response('json', Helper::showError('Некорректные данные'), 400);
        }

        $fileId = (int) $data['id'];
        $fileName = $data['fileName'];

        if (mb_strlen($fileName) > 255) {
            return new Response('json', Helper::showError('Название файла, вместе с расширением, не должно превышать 255 символов'), 400);
        }

        $file = App::getService('file');

        if (!$file->get(['id' => $fileId, 'userId' => $userId])) {
            return $this->pageNotFound();
        }

        if (!$file->rename($fileName)) {
            return new Response('json', Helper::showError('Такое имя уже существует в данной папке'), 400);
        }

        return new Response();
    }

    /**
     * Удаляет файл пользователя.
     *
     * @param array{0: string} $params Параметры запроса, где первый элемент - идентификатор файла.
     * @return Response Ответ на запрос.
     */
    public function remove(array $params): Response
    {
        if (!$this->isLogin()) {
            return $this->accessForbidden();
        }

        $userId = (int) $_SESSION['id'];

        if (!isset($params[0]) || !ctype_digit($params[0])) {
            return $this->pageNotFound();
        }

        $fileId = (int) $params[0];
        $file = App::getService('file');

        if (!$file->get(['id' => $fileId, 'userId' => $userId])) {
            return $this->pageNotFound();
        }

        $file->delete();

        return new Response();
    }

    /**
     * Метод для получения списка пользователей, с которыми был 
     * поделён файл.
     *
     * @param array<int, string> $params Массив параметров, где первый элемент - ID файла.
     * @return Response Ответ в формате JSON с данными о пользователях, с которыми был поделён файл.
     */
    public function shareList(array $params): Response
    {
        if (!$this->isLogin()) {
            return $this->accessForbidden();
        }

        if (!isset($params[0]) || !ctype_digit($params[0])) {
            return $this->pageNotFound();
        }

        $userId = (int) $_SESSION['id'];
        $fileId = (int) $params[0];
        $file = App::getService('file');

        if (!$file->get(['id' => $fileId]) || $file->userId !== $userId) {
            return $this->pageNotFound();
        }

        $data = App::getService('share')->list($fileId);

        return new Response('json', $data);
    }

    /**
     * Добавляет доступ к файлу для другого пользователя.
     *
     * @param array<int> $params Массив параметров, где первый элемент - ID файла, второй элемент - ID пользователя, которому предоставляется доступ.
     * @return Response Ответ с результатом выполнения операции.
     */
    public function addUserShareFile(array $params): Response
    {
        if (!$this->isLogin()) {
            return $this->accessForbidden();
        }

        $userId = (int) $_SESSION['id'];

        $result = $this->validateFileShareParams($params);

        if (!is_array($result)) {
            return $result;
        }

        [$fileId, $shareUserId] = $result;

        $file = App::getService('file');

        if (!$file->get(['id' => $fileId, 'userId' => $userId])) {
            return $this->pageNotFound();
        }

        if ($file->userId === $shareUserId) {
            return new Response('json', Helper::showError('Этот файл и так принадлежит пользователю'), 400);
        }

        $user = new User();

        if (!$user->get(['id' => $shareUserId])) {
            return $this->pageNotFound();
        }

        $share = App::getService('share');

        if (!$share->create($shareUserId, $fileId)) {
            return new Response('json', Helper::showError('Пользователь уже имеет доступ к этому файлу'), 400);
        }

        return new Response();
    }

    /**
     * Удаляет общий доступ к файлу для пользователя.
     *
     * @param array{fileId: int, shareUserId: int} $params Параметры, содержащие идентификатор файла и идентификатор пользователя, с которым делится файл.
     * @return Response Ответ на запрос, содержащий результат операции.
     */
    public function deleteUserShareFile(array $params): Response
    {
        if (!$this->isLogin()) {
            return $this->accessForbidden();
        }

        $userId = (int) $_SESSION['id'];

        $result = $this->validateFileShareParams($params);

        if (!is_array($result)) {
            return $result;
        }

        [$fileId, $shareUserId] = $result;

        $file = App::getService('file');

        if (!$file->get(['id' => $fileId, 'userId' => $userId])) {
            return $this->pageNotFound();
        }

        $share = App::getService('share');
        $share->delete($shareUserId, $fileId);

        return new Response();
    }

    /**
     * Загружает файл для авторизованного пользователя.
     *
     * @param Request $request Объект запроса, содержащий данные о запрашиваемом файле.
     * @return Response Ответ, содержащий сообщение об ошибке.
     */
    public function download(Request $request): Response
    {
        if (!$this->isLogin()) {
            return $this->accessForbidden();
        }

        $data = $request->getData()['GET'];

        if (!isset($data['file']) || !is_string($data['file'])) {
            return $this->pageNotFound();
        }

        $userId = (int) $_SESSION['id'];
        $serverName = $data['file'];

        $file = App::getService('file');

        if (!$file->get(['serverName' => $serverName])) {
            return $this->pageNotFound();
        }

        $fileShareUsersList = App::getService('share')::getUsers($file->id);

        if ($file->userId !== $userId && !in_array(['userId' => $userId], $fileShareUsersList)) {
            return $this->accessForbidden();
        }

        $filesStorage = App::getService('fileStorage');

        if ($filesStorage->fileForceDownload($file->serverName, $file->origenName, $file->mimeType) === false) {
            return $this->pageNotFound();
        }

        return new Response();
    }

    /**
     * Валидирует параметры для совместного использования файла.
     *
     * @param int $userId Идентификатор пользователя.
     * @param array<int, string> $params Массив параметров, содержащий идентификаторы файла и пользователя для совместного использования.
     * @return array<int, int>|Response Возвращает массив с идентификаторами файла и пользователя для совместного использования или объект ответа при ошибке.
     */
    private function validateFileShareParams(array $params): array | Response
    {
        if (count($params) !== 2) {
            return $this->pageNotFound();
        }

        foreach ($params as $parameter) {
            if (!isset($parameter) || !ctype_digit($parameter)) {
                return $this->pageNotFound();
            }
        }

        $fileId = (int) $params[0];
        $shareUserId = (int) $params[1];

        return [
            $fileId,
            $shareUserId
        ];
    }
}
