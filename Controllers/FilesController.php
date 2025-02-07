<?php

namespace Controllers;

use Core\App;
use Core\Config;
use Core\Helper;
use Core\Request;
use Core\Response;
use Core\Response\JSONResponse;
use Flow\Request as FlowRequest;

class FilesController
{
    public function list(): Response
    {
        if (!isset($_SESSION['id'])) {
            return new JSONResponse(Helper::showError('Доступ запрещен'), 403);
        }

        return App::getService('fileService')->getFilesList((int) $_SESSION['id']);
    }

    public function getFile(array $params): Response
    {
        if (!isset($_SESSION['id'])) {
            return new JSONResponse(Helper::showError('Доступ запрещен'), 403);
        }

        if (!isset($params[0]) || !ctype_digit($params[0])) {
            return new Response('html', 'Страница не найдена', 404);
        }

        $fileId = (int) $params[0];

        return App::getService('fileService')->getUserFile($fileId);
    }

    public function add(Request $request): Response
    {
        if (!isset($_SESSION['id'])) {
            return new JSONResponse(Helper::showError('Доступ запрещен'), 403);
        }

        $userId = $_SESSION['id'];

        if (!isset($request->getData()['FILES']['file'])) {
            return new JSONResponse(Helper::showError('Файл не передан'), 400);
        }

        if ($request->getData()['FILES']['file']['error'] !== 0) {
            return new JSONResponse(Helper::showError('Файл не загружен'), 400);
        }

        $flowRequest = new FlowRequest();
        $folderId = $flowRequest->getParam('folderId');

        if (!isset($folderId) || !ctype_digit($folderId)) {
            return new JSONResponse(Helper::showError('Не передан folderId или его значение не корректно'), 400);
        }

        $maxFileSize = Config::getConfig('app.uploadFile.maxFileSize');
        $finalFileSize = $flowRequest->getParam('finalSize');

        if ($finalFileSize > $maxFileSize) {
            return new JSONResponse(Helper::showError('Превышен максимальный размер в ' . $maxFileSize . ' байт'), 400);
        }

        $fileName = $flowRequest->getFileName();

        if ($fileName === null) {
            return new JSONResponse(Helper::showError('Название файла не передано, для загрузки файлов необходимо использовать скрипт flow.js'), 400);
        }

        if (mb_strlen($fileName) > 255) {
            return new JSONResponse(Helper::showError('Название файла, вместе с расширением, не должно превышать 255 символов'), 400);
        }

        return App::getService('fileService')->addFileChunks((int) $userId, (int) $folderId, $flowRequest);
    }

    public function renameFile(Request $request): Response
    {
        if (!isset($_SESSION['id'])) {
            return new JSONResponse(Helper::showError('Доступ запрещен'), 403);
        }

        $userId = (int) $_SESSION['id'];

        $data = $request->getData();

        if (
            !isset($data['PUT']['id']) ||
            !ctype_digit($data['PUT']['id']) ||
            !isset($data['PUT']['fileName']) ||
            !is_string($data['PUT']['fileName'])
        ) {
            return new JSONResponse(Helper::showError('Некорректные данные'), 400);
        }

        $fileId = (int) $data['PUT']['id'];
        $fileName = $data['PUT']['fileName'];

        if (mb_strlen($fileName) > 255) {
            return new JSONResponse(Helper::showError('Название файла, вместе с расширением, не должно превышать 255 символов'), 400);
        }

        return App::getService('fileService')->renameUserFile($userId, $fileId, $fileName);
    }

    public function removeFile(array $params): Response
    {
        if (!isset($_SESSION['id'])) {
            return new JSONResponse(Helper::showError('Доступ запрещен'), 403);
        }

        $userId = (int) $_SESSION['id'];

        if (!isset($params[0]) || !ctype_digit($params[0])) {
            return new Response('html', 'Страница не найдена', 404);
        }

        $fileId = (int) $params[0];

        return App::getService('fileService')->deleteUserFile($userId, $fileId);
    }
}
