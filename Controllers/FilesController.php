<?php

namespace Controllers;

use Core\App;
use Core\Config;
use Core\Helper;
use Core\Request;
use Core\Response;
use Flow\Request as FlowRequest;
use traits\PageTrait;
use traits\UserTrait;

class FilesController
{
    use UserTrait;
    use PageTrait;

    /**
     * Возвращает список файлов пользователя.
     *
     * @return Response Ответ с авторизацией пользователя или список файлов.
     */
    public function list(): Response
    {
        if ($response = $this->checkUserAuthorization()) {
            return $response;
        }

        return App::getService('fileService')->getFilesList((int) $_SESSION['id']);
    }

    /**
     * Получает файл пользователя по идентификатору файла.
     *
     * @param array{0: int} $params Массив параметров, где первый элемент - идентификатор файла.
     * @return Response Ответ с описанием файла пользователя или результат валидации.
     */
    public function getFile(array $params): Response
    {
        if ($response = $this->validationSessionIdAndIntParams($params)) {
            return $response;
        }

        $userId = (int) $_SESSION['id'];
        $fileId = (int) $params[0];

        return App::getService('fileService')->getUserFile($userId, $fileId);
    }

    /**
     * Добавляет новый файл, загруженный пользователем.
     *
     * @param Request $request Объект запроса, содержащий данные о загружаемом файле.
     * @return Response JSON-ответ с результатом операции.
     *
     * Проверяет авторизацию пользователя, наличие и корректность переданных данных о файле и папке,
     * а также размер файла и его название. Если все проверки пройдены, добавляет файл в хранилище.
     */
    public function add(Request $request): Response
    {
        if ($response = $this->checkUserAuthorization()) {
            return $response;
        }

        $userId = $_SESSION['id'];

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

        return App::getService('fileService')->addFileChunks((int) $userId, (int) $folderId, $flowRequest);
    }

    /**
     * Переименовывает файл пользователя.
     *
     * @param Request $request Объект запроса, содержащий данные для переименования файла.
     *                         Ожидается, что данные будут в формате:
     *                         [
     *                             'PUT' => [
     *                                 'id' => string, // Идентификатор файла в виде строки, содержащей только цифры.
     *                                 'fileName' => string // Новое имя файла.
     *                             ]
     *                         ]
     * @return Response JSON-ответ с результатом операции.
     */
    public function rename(Request $request): Response
    {
        if ($response = $this->checkUserAuthorization()) {
            return $response;
        }

        $userId = (int) $_SESSION['id'];

        $data = $request->getData();

        if (
            !isset($data['PUT']['id']) ||
            !ctype_digit($data['PUT']['id']) ||
            !isset($data['PUT']['fileName']) ||
            !is_string($data['PUT']['fileName'])
        ) {
            return new Response('json', Helper::showError('Некорректные данные'), 400);
        }

        $fileId = (int) $data['PUT']['id'];
        $fileName = $data['PUT']['fileName'];

        if (mb_strlen($fileName) > 255) {
            return new Response('json', Helper::showError('Название файла, вместе с расширением, не должно превышать 255 символов'), 400);
        }

        return App::getService('fileService')->renameUserFile($userId, $fileId, $fileName);
    }

    /**
     * Удаляет файл пользователя.
     *
     * @param array<int> $params Массив параметров, где первый элемент - ID файла.
     * @return Response Ответ сервера.
     */
    public function remove(array $params): Response
    {
        if ($response = $this->checkUserAuthorization()) {
            return $response;
        }

        $userId = (int) $_SESSION['id'];

        if ($response = $this->checkNotFoundPage($params[0])) {
            return $response;
        }

        $fileId = (int) $params[0];

        return App::getService('fileService')->deleteUserFile($userId, $fileId);
    }

    /**
     * Возвращает список пользователей, с которыми файл был разделён.
     *
     * @param array<int> $params Массив параметров, где первый элемент - идентификатор файла.
     * @return Response Ответ с данными о пользователях, с которыми файл был разделен.
     */
    public function shareList(array $params): Response
    {
        if ($response = $this->validationSessionIdAndIntParams($params)) {
            return $response;
        }

        $userId = (int) $_SESSION['id'];
        $fileId = (int) $params[0];

        return App::getService('fileService')->getShareList($userId, $fileId);
    }

    /**
     * Добавляет файл для совместного использования пользователем.
     *
     * @param array<int, int> $params Массив параметров, содержащий идентификаторы файла и пользователя для совместного использования.
     * @return Response Ответ сервера.
     */
    public function addUserShareFile(array $params): Response
    {
        if ($response = $this->validationSessionIdAndIntParams($params)) {
            return $response;
        }

        $userId = (int) $_SESSION['id'];
        [$fileId, $shareUserId] = $params;

        return App::getService('fileService')->addUserShareFile((int) $userId, (int) $fileId, (int) $shareUserId);
    }

    /**
     * Удаляет общий доступ к файлу для пользователя.
     *
     * @param array<int, int> $params Массив параметров, содержащий идентификаторы файла и пользователя, с которым делится файл.
     * @return Response Ответ сервера.
     */
    public function deleteUserShareFile(array $params): Response
    {
        if ($response = $this->validationSessionIdAndIntParams($params)) {
            return $response;
        }

        $userId = (int) $_SESSION['id'];
        [$fileId, $shareUserId] = $params;

        return App::getService('fileService')->deleteUserShareFile((int) $userId, (int) $fileId, (int) $shareUserId);
    }

    /**
     * Загружает файл для авторизованного пользователя.
     *
     * @param Request $request Объект запроса, содержащий данные о запрашиваемом файле.
     * @return Response Ответ, содержащий сообщение об ошибке.
     */
    public function download(Request $request): Response
    {
        if ($response = $this->checkUserAuthorization()) {
            return $response;
        }

        if (!isset($request->getData()['GET']['file']) || !is_string($request->getData()['GET']['file'])) {
            return new Response('html', 'Страница не найдена', 404);
        }

        $userId = (int) $_SESSION['id'];
        $serverName = $request->getData()['GET']['file'];

        return App::getService('fileService')->downloadFile($userId, $serverName);
    }

    /**
     * Проверяет авторизацию пользователя и наличие параметров в запросе.
     *
     * @param array<int> $params Массив параметров, которые должны быть целыми числами.
     * @return Response|null Возвращает объект ответа в случае ошибки, либо null, если проверка прошла успешно.
     */
    private function validationSessionIdAndIntParams(array $params): ?Response
    {
        if ($response = $this->checkUserAuthorization()) {
            return $response;
        }

        foreach ($params as $parameter) {
            if ($response = $this->checkNotFoundPage($parameter)) {
                return $response;
            }
        }

        return null;
    }
}
