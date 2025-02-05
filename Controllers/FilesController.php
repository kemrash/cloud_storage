<?php

namespace Controllers;

use Core\App;
use Core\Config;
use Core\Helper;
use Core\Request;
use Core\Response;
use Core\Response\JSONResponse;

class FilesController
{
    public function add(Request $request): Response
    {
        if (!isset($_SESSION['id'])) {
            return new JSONResponse(Helper::showError('Доступ запрещен'), 403);
        }

        if (!isset($request->getData()['FILES']['file'])) {
            return new JSONResponse(Helper::showError('Файл не передан'), 400);
        }

        if ($request->getData()['FILES']['file']['error'] !== 0) {
            return new JSONResponse(Helper::showError('Файл не загружен'), 400);
        }

        $maxFileSize = Config::getConfig('app.uploadFile.maxFileSize');

        if ($request->getData()['FILES']['file']['size'] > $maxFileSize) {
            return new JSONResponse(Helper::showError('Превышен максимальный размер: ' . $maxFileSize . ' байт'), 400);
        }

        if (mb_strlen($request->getData()['FILES']['file']['name']) > 255) {
            return new JSONResponse(Helper::showError('Название файла не должно превышать 255 символов'), 400);
        }

        if (!isset($request->getData()['POST']['folderId']) || !ctype_digit($_POST['folderId'])) {
            return new JSONResponse(Helper::showError('Не передан folderId или его значение не корректно'), 400);
        }

        $userId = (int) $_SESSION['id'];
        $folderId = (int) $request->getData()['POST']['folderId'];

        return App::getService('fileService')->addFiles($userId, $folderId, $request->getData()['FILES']['file']);
    }
}
