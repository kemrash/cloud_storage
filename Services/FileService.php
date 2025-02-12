<?php

namespace Services;

use Core\App;
use Core\AppException;
use Core\Config;
use Core\Db;
use Core\FileStorage;
use Core\Helper;
use Core\Response;
use Core\Response\JSONResponse;
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
    public function getFilesList(int $id): Response
    {
        $data = App::getService('fileRepository')::getFilesListUser($id);

        return new JSONResponse($data);
    }

    public function getUserFile(int $fileId): Response
    {
        $file = App::getService('fileRepository')::getFileById($fileId);

        if ($file === null) {
            return new Response('html', 'Страница не найдена', 404);
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

    public function addFileChunks(int $userId, int $folderId, FlowRequest $request): Response
    {
        try {
            $folder = App::getService('folderRepository')::findOneFolderById($folderId);

            if ($folder === null || $folder->userId !== $userId) {

                if ($folder === null) {
                    return new JSONResponse(Helper::showError('Папка не найдена'), 400);
                }

                return new JSONResponse(Helper::showError('Доступ запрещен'), 403);
            }

            $config = new FlowConfig();
            $chunksTempFolder = './Chunks_temp_folder';
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

                    return new JSONResponse(Helper::showError('Доступ запрещен'), 403);
                }

                $maxAttempts = 2;
                $origenFileName = $request->getFileName();
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

    public function renameUserFile(int $userId, int $fileId, string $name): Response
    {
        return $this->processUserFileWithTransaction($userId, $fileId, 'fileRepository', 'renameFile', [$fileId, $name]);
    }

    public function deleteUserFile(int $userId, int $fileId): Response
    {
        return $this->processUserFileWithTransaction($userId, $fileId, 'fileRepository', 'deleteFile', [$fileId], true);
    }

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
            $file = App::getService('fileRepository')::getFileById($fileId);

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

    private function randomClearFolderChunks(string $folder): void
    {
        if (1 == mt_rand(1, 100)) {
            Uploader::pruneChunks($folder);
        }
    }
}
