<?php

namespace Services;

use Core\App;
use Core\AppException;
use Core\Config;
use Core\Db;
use Core\FileStorage;
use Core\Helper;
use Core\Response;
use Core\Response\AccessDeniedResponse;
use Core\Response\JSONResponse;
use Core\Response\PageNotFoundResponse;
use Exception;
use PDOException;
use Ramsey\Uuid\Uuid;
use Flow\Config as FlowConfig;
use Flow\Request as FlowRequest;
use Flow\Basic;
use Flow\Uploader;
use Models\File;

class FileService
{
    /**
     * Получает список файлов для пользователя по его идентификатору.
     *
     * @param int $id Идентификатор пользователя.
     * @return Response JSON-ответ с данными о файлах.
     */
    public function getFilesList(int $id): Response
    {
        $data = App::getService('fileRepository')::getFilesListUser($id);

        return new JSONResponse($data);
    }

    /**
     * Получает файл пользователя по его идентификатору и идентификатору файла.
     *
     * @param int $userId Идентификатор пользователя.
     * @param int $fileId Идентификатор файла.
     * @return Response Возвращает JSON-ответ с данными файла, если доступ разрешен, 
     *                  или ответ с ошибкой, если файл не найден или доступ запрещен.
     */
    public function getUserFile(int $userId, int $fileId): Response
    {
        $file = App::getService('fileRepository')::getFileBy(['id' => $fileId]);

        if ($file === null) {
            return new PageNotFoundResponse();
        }

        $fileShareUsersList = App::getService('fileRepository')::getUsersFileShare($fileId);

        if ($file->userId !== $userId && !in_array(['userId' => $userId], $fileShareUsersList)) {
            return new AccessDeniedResponse();
        }

        return new JSONResponse([
            'id' => $file->id,
            'userId' => $file->userId,
            'serverName' => $file->serverName,
            'folderId' => $file->folderId,
            'origenName' => $file->origenName,
            'mimeType' => $file->mimeType,
            'size' => $file->size,
        ]);
    }

    /**
     * Добавляет чанки файла в указанную папку пользователя.
     *
     * @param int $userId Идентификатор пользователя.
     * @param int $folderId Идентификатор папки.
     * @param FlowRequest $request Объект запроса, содержащий информацию о чанках файла.
     * @return Response Ответ с результатом операции.
     * @throws Exception В случае ошибки при создании директорий или сохранении файла.
     * @throws AppException В случае ошибки при удалении файла или другой ошибки приложения.
     */
    public function addFileChunks(int $userId, int $folderId, FlowRequest $request): Response
    {
        try {
            $folder = App::getService('folderRepository')::findOneFolderById($folderId);

            if ($folder === null || $folder->userId !== $userId) {

                if ($folder === null) {
                    return new JSONResponse(Helper::showError('Папка не найдена'), 400);
                }

                return new AccessDeniedResponse();
            }

            $origenFileName = $request->getFileName();

            $file = App::getService('fileRepository')::getFileBy(['folderId' => $folderId, 'origenName' => $origenFileName]);

            if ($file !== null) {
                return new JSONResponse(Helper::showError('Файл с таким именем уже существует в этой папке'), 400);
            }

            $config = new FlowConfig();
            $chunksTempFolder = './chunks_temp_folder';
            $uploadFolder = Config::getConfig('app.uploadFile.folderFileStorage');

            if (!is_dir($chunksTempFolder) && !mkdir($chunksTempFolder, 0755, true)) {
                throw new Exception("Не удалось создать временную директорию: {$chunksTempFolder}");
            }

            if (!is_dir($uploadFolder) && !mkdir($uploadFolder, 0755, true)) {
                throw new Exception("Не удалось создать директорию для хранения файлов: {$uploadFolder}");
            }

            $config->setTempDir($chunksTempFolder);
            $uploadFileName = uniqid('', true) . "_" . Uuid::uuid4()->toString();
            $uploadPath = $uploadFolder . $uploadFileName;

            if (Basic::save($uploadPath, $config, $request)) {
                $connection = Db::$connection;
                $connection->beginTransaction();

                $folder = App::getService('folderRepository')::findOneFolderById($folderId);

                if ($folder === null || $folder->userId !== $userId) {
                    $connection->rollBack();

                    if ($folder === null) {
                        return new JSONResponse(Helper::showError('Папка не найдена'), 400);
                    }

                    return new AccessDeniedResponse();
                }

                $maxAttempts = 2;
                $fileSize = filesize($uploadPath);
                $fileMimeType = mime_content_type($uploadPath);

                if ($fileMimeType === false) {
                    $fileMimeType = '';
                }

                for ($i = 0; $i <= $maxAttempts; $i++) {

                    $file = new File($userId, $folderId, $uploadFileName, $origenFileName, $fileMimeType, (int) $fileSize);

                    try {
                        App::getService('fileRepository')::addFile($file);
                        $connection->commit();
                        break;
                    } catch (PDOException $e) {
                        if ($e->getCode() !== '23000') {
                            $connection->rollBack();
                            throw new Exception($e->getMessage());
                        }
                    }

                    if ($i === $maxAttempts) {
                        $connection->rollBack();
                        throw new Exception("Не удалось сгенерировать уникальное имя файла после {$maxAttempts} попыток.");
                    }

                    $oldFileName = $uploadFileName;
                    $uploadFileName = uniqid('', true) . "_" . Uuid::uuid4()->toString();

                    try {
                        rename($uploadFolder . $oldFileName, $uploadFolder . $uploadFileName);
                    } catch (Exception $e) {
                        throw new Exception($e->getMessage());
                    }
                }

                $this->randomClearFolderChunks($chunksTempFolder);

                return new JSONResponse([
                    'status' => 'success',
                    'message' => 'Файл успешно загружен',
                    'path' => $uploadPath
                ]);
            }

            return new JSONResponse([
                'status' => 'continue',
                'message' => 'Чанк получен, продолжается загрузка'
            ]);
        } catch (Exception $e) {
            if (isset($uploadFileName) && file_exists($uploadFolder . $uploadFileName)) {
                try {
                    unlink($uploadFolder . $uploadFileName);
                } catch (Exception $e) {
                    throw new AppException(__CLASS__, $e->getMessage());
                }
            }

            throw new AppException(__CLASS__, $e->getMessage());
        }
    }

    /**
     * Переименовывает файл пользователя.
     *
     * @param int $userId Идентификатор пользователя.
     * @param int $fileId Идентификатор файла.
     * @param string $name Новое имя файла.
     * @return Response Ответ на запрос.
     */
    public function renameUserFile(int $userId, int $fileId, string $name): Response
    {
        return $this->processUserFileWithTransaction($userId, $fileId, 'fileRepository', 'renameFile', [$fileId, $name]);
    }

    /**
     * Удаляет файл пользователя.
     *
     * @param int $userId Идентификатор пользователя.
     * @param int $fileId Идентификатор файла.
     * @return Response Ответ после выполнения операции.
     */
    public function deleteUserFile(int $userId, int $fileId): Response
    {
        return $this->processUserFileWithTransaction($userId, $fileId, 'fileRepository', 'deleteFile', [$fileId], true);
    }

    /**
     * Получает список пользователей, с которыми был поделён файл.
     *
     * @param int $userId Идентификатор пользователя, запрашивающего список.
     * @param int $fileId Идентификатор файла, для которого запрашивается список.
     * @return Response Ответ в формате JSON, содержащий список пользователей, с которыми был поделён файл.
     */
    public function getShareList(int $userId, int $fileId): Response
    {
        $file = App::getService('fileRepository')::getFileBy(['id' => $fileId]);

        if ($file === null || $file->userId !== $userId) {
            return new AccessDeniedResponse();
        }

        $data = App::getService('fileRepository')::getFileShareList($fileId);

        return new JSONResponse($data);
    }

    /**
     * Добавляет доступ к файлу для другого пользователя.
     *
     * @param int $userId Идентификатор пользователя, который делится файлом.
     * @param int $fileId Идентификатор файла, которым делятся.
     * @param int $shareUserId Идентификатор пользователя, которому предоставляется доступ к файлу.
     * @return Response Ответ на запрос.
     * @throws AppException В случае возникновения ошибки при выполнении операции.
     */
    public function addUserShareFile(int $userId, int $fileId, int $shareUserId): Response
    {
        $connection = Db::$connection;
        $connection->beginTransaction();

        try {
            $file = App::getService('fileRepository')::getFileBy(['id' => $fileId]);

            if ($file === null || $file->userId !== $userId) {
                $connection->rollBack();
                return new AccessDeniedResponse();
            }

            $user = App::getService('userRepository')::getUserBy(['id' => $shareUserId]);

            if ($user === null) {
                $connection->rollBack();
                return new JSONResponse(Helper::showError('Пользователь не найден'), 400);
            }

            if ($file->userId === $shareUserId) {
                $connection->rollBack();
                return new JSONResponse(Helper::showError('Этот файл и так принадлежит пользователю'), 400);
            }

            try {
                App::getService('fileRepository')::addUserShareFile($shareUserId, $fileId);

                $connection->commit();

                return new JSONResponse();
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $connection->rollBack();

                    return new JSONResponse(Helper::showError('Пользователь уже имеет доступ к этому файлу'), 400);
                }

                throw new Exception($e->getMessage());
            }
        } catch (Exception $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw new AppException(__CLASS__, $e->getMessage());
        }
    }

    /**
     * Удаляет общий доступ к файлу для указанного пользователя.
     *
     * @param int $userId Идентификатор пользователя, который владеет файлом.
     * @param int $fileId Идентификатор файла, доступ к которому нужно удалить.
     * @param int $shareUserId Идентификатор пользователя, у которого нужно удалить доступ к файлу.
     * @return Response Возвращает JSON-ответ в случае успешного удаления или ответ с отказом в доступе.
     */
    public function deleteUserShareFile(int $userId, int $fileId, int $shareUserId): Response
    {
        $file = App::getService('fileRepository')::getFileBy(['id' => $fileId]);

        if ($file === null || $file->userId !== $userId) {
            return new AccessDeniedResponse();
        }

        App::getService('fileRepository')::deleteShareBy($shareUserId, $fileId);

        return new JSONResponse();
    }

    /**
     * Загружает файл для указанного пользователя.
     *
     * @param int $userId Идентификатор пользователя, запрашивающего файл.
     * @param string $serverName Имя файла на сервере.
     * @return Response|null Возвращает ответ ошибки или прекращает работу скрипта после отправки файла.
     */
    public function downloadFile(int $userId, string $serverName): ?Response
    {
        $file = App::getService('fileRepository')::getFileBy(['serverName' => $serverName]);

        if ($file === null) {
            return new PageNotFoundResponse();
        }

        $fileShareUsersList = App::getService('fileRepository')::getUsersFileShare($file->id);

        if ($file->userId !== $userId && !in_array(['userId' => $userId], $fileShareUsersList)) {
            return new JSONResponse(Helper::showError('Доступ запрещен'), 403);
        }

        $filesStorage = new FileStorage();

        if ($filesStorage->fileForceDownload($file->serverName, $file->origenName, $file->mimeType) === false) {
            return new PageNotFoundResponse();
        }
    }

    /**
     * Обрабатывает файл пользователя с использованием транзакции.
     *
     * @param int $userId Идентификатор пользователя.
     * @param int $fileId Идентификатор файла.
     * @param string $serviceName Название сервиса, который будет вызван.
     * @param string $method Метод сервиса, который будет вызван.
     * @param array<string, mixed> $params Параметры, передаваемые в метод сервиса.
     * @param bool $isDeleteFile Флаг, указывающий, нужно ли удалить файл после обработки.
     *
     * @return Response Возвращает объект ответа.
     *
     * @throws AppException В случае возникновения ошибки в процессе выполнения.
     */
    private function processUserFileWithTransaction(
        int $userId,
        int $fileId,
        string $serviceName,
        string $method,
        array $params,
        bool $isDeleteFile = false
    ): Response {
        $connection = Db::$connection;
        $connection->beginTransaction();

        try {
            $file = App::getService('fileRepository')::getFileBy(['id' => $fileId]);

            if ($file === null || $file->userId !== $userId) {
                $connection->rollBack();

                return new JSONResponse(Helper::showError('Доступ запрещен'), 403);
            }

            call_user_func_array([App::getService($serviceName), $method], $params);

            if ($isDeleteFile) {
                $filesStorage = new FileStorage();
                $filesStorage->deleteFile($file->serverName);
            }

            $connection->commit();

            return new JSONResponse();
        } catch (Exception $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw new AppException(__CLASS__, $e->getMessage());
        }
    }

    /**
     * Удаляет случайные части файлов в указанной папке.
     *
     * Этот метод с вероятностью 1% вызывает метод pruneChunks класса Uploader,
     * который очищает части файлов в указанной папке.
     *
     * @param string $folder Путь к папке, в которой необходимо удалить части файлов.
     *
     * @return void
     */
    private function randomClearFolderChunks(string $folder): void
    {
        if (1 == mt_rand(1, 100)) {
            Uploader::pruneChunks($folder);
        }
    }
}
