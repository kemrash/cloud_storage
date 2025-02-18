<?php

namespace Services;

use Core\App;
use Core\AppException;
use Core\Db;
use Core\FileStorage;
use Core\Helper;
use Core\Response;
use Core\Response\JSONResponse;
use Core\Response\PageNotFoundResponse;
use Models\Folder;
use PDOException;

class FolderService
{
    /**
     * Возвращает список папок пользователя.
     *
     * @param int $userId Идентификатор пользователя.
     * @return Response JSON-ответ с данными о папках пользователя.
     */
    public function getUserFoldersList(int $userId): Response
    {
        $data = App::getService('folderRepository')::getUserFoldersList($userId);

        return new JSONResponse($data);
    }

    /**
     * Создает папку пользователя.
     *
     * @param int $userId Идентификатор пользователя.
     * @param int $parentId Идентификатор родительской папки. Не может быть 0.
     * @param string $name Название папки.
     * @return Response JSON-ответ с результатом операции.
     * @throws AppException В случае возникновения ошибки при добавлении папки.
     */
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

    /**
     * Переименовывает папку пользователя.
     *
     * @param int $userId Идентификатор пользователя.
     * @param int $id Идентификатор папки.
     * @param string $name Новое имя папки.
     * @return Response Возвращает объект ответа, который может быть либо JSONResponse с данными, либо PageNotFoundResponse, если папка не найдена.
     */
    public function renameUserFolder(int $userId, int $id, string $name): Response
    {
        $data = App::getService('folderRepository')::renameFolder($userId, $id, $name);

        return isset($data['code']) && $data['code'] === 404 ? new PageNotFoundResponse() : new JSONResponse($data);
    }

    /**
     * Получает папку пользователя по идентификатору пользователя и идентификатору папки.
     *
     * @param int $userId Идентификатор пользователя.
     * @param int $folderId Идентификатор папки.
     * @return Response Возвращает JSON-ответ с данными папки или ответ о не нахождении страницы.
     */
    public function getUserFolder(int $userId, int $folderId): Response
    {
        $folder = App::getService('folderRepository')::getFolderBy($userId, $folderId);

        if ($folder === null) {
            return new PageNotFoundResponse();
        }

        return new JSONResponse([
            'id' => $folder->id,
            'userId' => $folder->userId,
            'parentId' => $folder->parentId,
            'name' => $folder->name
        ]);
    }

    /**
     * Удаляет папку пользователя и все файлы в ней.
     *
     * @param int $userId Идентификатор пользователя.
     * @param int $folderId Идентификатор папки.
     * @return Response Ответ с результатом выполнения операции.
     */
    public function removeUserFolder(int $userId, int $folderId): Response
    {
        $folder = App::getService('folderRepository')::getFolderBy($userId, $folderId);

        if ($folder === null) {
            return new PageNotFoundResponse();
        }

        if ($folder->parentId === 0) {
            return new JSONResponse(Helper::showError('Нельзя удалить корневую папку'), 400);
        }

        $filesList = $this->deleteFolderAndReturnFilesList((int) $folder->id);

        $filesStorage = new FileStorage();
        $filesStorage->deleteFiles($filesList);

        return new JSONResponse();
    }

    /**
     * Удаляет папку и возвращает список файлов в ней.
     *
     * @param int $folderId Идентификатор папки, которую необходимо удалить.
     * @param bool $isTransaction Флаг, указывающий, является ли операция частью внешней транзакции. По умолчанию false.
     * @return array<int, array<string, string|int>> Список файлов в удаленной папке, где каждый элемент массива представляет собой ассоциативный массив с данными файла.
     * @throws AppException В случае ошибки при выполнении операции удаления.
     */
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
