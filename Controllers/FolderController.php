<?php

namespace Controllers;

use Core\App;
use Core\Helper;
use Core\Request;
use Core\Response;
use Core\Response\JSONResponse;

class FolderController
{
    public function add(Request $request): Response
    {
        if (!isset($_SESSION['id'])) {
            return new JSONResponse(Helper::showError('Доступ запрещен'), 403);
        }

        $postData = $request->getData()['POST'];

        if (!isset($postData['parentId']) || !ctype_digit($postData['parentId'])) {
            return new JSONResponse(Helper::showError('Не передан parentId или его значение не корректно'), 400);
        }

        if (!isset($postData['name']) || !is_string($postData['name']) || mb_strlen($postData['name']) > 255) {
            return new JSONResponse(Helper::showError('Не передан name или его больше 255 символов'), 400);
        }

        $userId = (int) $_SESSION['id'];
        $parentId = (int) $postData['parentId'];
        $name = $postData['name'];

        return App::getService('folderService')->createUserFolder($userId, $parentId, $name);
    }
}
