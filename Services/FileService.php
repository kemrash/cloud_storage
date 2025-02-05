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

class FileService
{
    public function addFiles(int $userId, int $folderId, array $file): Response
    {
        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . Config::getConfig('app.uploadFile.folderFileStorage');
        $idFile = null;
        $maxAttempts = 5;
        $attempt = 0;

        $connection = Db::$connection;
        $connection->beginTransaction();

        try {
            $folder = App::getService('fileRepository')::findOneFolderById($folderId);

            if ($folder === null || $folder->userId !== $userId) {
                $connection->rollBack();

                if ($folder === null) {
                    return new JSONResponse(Helper::showError('Папка не найдена'));
                }

                return new JSONResponse(Helper::showError('Доступ запрещен'));
            }

            do {
                $attempt++;
                $serverName = Uuid::uuid4()->toString();

                try {
                    $idFile = App::getService('fileRepository')::addFile($userId, $folderId, $serverName, $file);
                    $connection->commit();
                    break;
                } catch (PDOException $e) {
                    if ($e->getCode() !== '23000') {
                        throw new Exception($e->getMessage());
                    }
                }

                if ($attempt >= $maxAttempts) {
                    throw new Exception("Не удалось сгенерировать уникальное имя файла после {$maxAttempts} попыток.");
                }
            } while (true);

            $data = FileStorage::saveFile($uploadDir, $serverName, $file);

            if (!isset($data['status']) || $data['status'] !== 'ok') {
                App::getService('fileRepository')::deleteFile((int) $idFile);

                if (!isset($data)) {
                    throw new Exception('Не удалось сохранить файл.');
                }

                return new JSONResponse($data);
            }

            return new JSONResponse();
        } catch (Exception $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            if ($idFile !== null) {
                try {
                    App::getService('fileRepository')::deleteFile((int) $idFile);
                } catch (Exception $e) {
                    throw new AppException(__CLASS__, $e->getMessage());
                }
            }

            throw new AppException(__CLASS__, $e->getMessage());
        }
    }
}
