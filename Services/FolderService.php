<?php

namespace Services;

use Core\App;
use Core\AppException;
use Core\Db;
use Core\FileStorage;
use Core\Helper;
use Core\Response;
use Core\Response\JSONResponse;
use Models\Folder;
use PDOException;

class FolderService
{
    public function getUserFoldersList(int $userId): Response
    {
        $data = App::getService('folderRepository')::getUserFoldersList($userId);

        return new JSONResponse($data);
    }

    public function createUserFolder(int $userId, int $parentId, string $name): Response
    {
        if ($parentId === 0) {
            return new JSONResponse(Helper::showError('Нельзя создать еще одну корневую папку'), 400);
        }

        $folderId = null;
        $folder = new Folder($userId, $parentId, $name);

        try {
            $folderId = App::getService('folderRepository')::addFolder($folder);

            return new JSONResponse([
                'status' => 'ok',
                'folderId' => $folderId
            ]);
        } catch (PDOException $e) {
            $connection = Db::$connection;

            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            if ($e->getCode() === '23000') {
                return new JSONResponse(Helper::showError('Папка с такими параметрами уже существует'), 400);
            }

            throw new AppException(__CLASS__, $e->getMessage());
        }
    }

    public function renameUserFolder(int $userId, int $id, string $name): Response
    {
        $data = App::getService('folderRepository')::renameFolder($userId, $id, $name);

        return isset($data['code']) && $data['code'] === 404 ? new Response('html', 'Страница не найдена', 404) : new JSONResponse($data);
    }

    public function getUserFolder(int $userId, int $folderId): Response
    {
        $folder = App::getService('folderRepository')::getFolderBy($userId, $folderId);

        if ($folder === null) {
            return new Response('html', 'Страница не найдена', 404);
        }

        return new JSONResponse([
            'id' => $folder->id,
            'userId' => $folder->userId,
            'parentId' => $folder->parentId,
            'name' => $folder->name
        ]);
    }

    public function removeUserFolder(int $userId, int $folderId): Response
    {
        $folder = App::getService('folderRepository')::getFolderBy($userId, $folderId);

        if ($folder === null) {
            return new Response('html', 'Страница не найдена', 404);
        }

        if ($folder->parentId === 0) {
            return new JSONResponse(Helper::showError('Нельзя удалить корневую папку'), 400);
        }

        $filesList = $this->deleteFolderAndReturnFilesList((int) $folder->id);

        $filesStorage = new FileStorage();
        $filesStorage->deleteFiles($filesList);

        return new JSONResponse();
    }

    public function deleteFolderAndReturnFilesList(int $folderId, bool $isTransaction = false): array
    {
        $filesList = [];

        $connection = Db::$connection;

        if (!$isTransaction) {
            $connection->beginTransaction();
        }

        try {
            $filesList = App::getService('fileRepository')::getFilesListFolder($folderId);
            App::getService('folderRepository')::deleteFilesAndFolderByFolderId($folderId);

            if (!$isTransaction) {
                $connection->commit();
            }
        } catch (PDOException $e) {
            if (!$isTransaction && $connection->inTransaction()) {
                $connection->rollBack();
            }

            throw new AppException(__CLASS__, $e->getMessage());
        }

        return $filesList;
    }
}
