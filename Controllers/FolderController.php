<?php

namespace Controllers;

use Core\App;
use Core\Helper;
use Core\Request;
use Core\Response;
use Core\Response\JSONResponse;
use Traits\UserTrait;

class FolderController
{
    use UserTrait;

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

    public function get(array $params): Response
    {
        if ($response = $this->checkUserAuthorization()) {
            return $response;
        }

        if (!isset($params[0]) || !ctype_digit($params[0])) {
            return new Response('html', 'Страница не найдена', 404);
        }

        $userId = (int) $_SESSION['id'];
        $folderId = (int) $params[0];

        return App::getService('folderService')->getUserFolder($userId, $folderId);
    }

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
