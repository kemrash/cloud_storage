<?php

namespace Services;

use Core\App;
use Core\AppException;
use Core\Config;
use Core\Db;
use Core\Helper;
use Core\Response;
use Core\Response\JSONResponse;
use Models\Folder;
use PDOException;

class FolderService
{
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

        $this->deleteFolderAndFiles((int) $folder->id);

        return new JSONResponse();
    }

    public function deleteFolderAndFiles(int $folderId, bool $isTransaction = false): void
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

        if (count($filesList) === 0) {
            return;
        }

        $pathFilesStorage = Config::getConfig('app.uploadFile.folderFileStorage');

        foreach ($filesList as $file) {
            $fullPath = $pathFilesStorage . $file['serverName'];

            if (file_exists($fullPath)) {
                if (!unlink($fullPath)) {
                    Helper::writeLog("Ошибка при удалении файла {$fullPath}");
                }
            }
        }
    }
}
