<?php

namespace Services;

use Core\App;
use Core\AppException;
use Core\Config;
use Core\Db;
use Core\Helper;
use Core\Response\JSONResponse;
use Exception;
use PDOException;
use Ramsey\Uuid\Uuid;
use Flow\Config as FlowConfig;
use Flow\Request as FlowRequest;
use Flow\Basic as FlowBasic;
use Flow\Uploader;
use Models\File;

class FileService
{
    public function addFileChunks(int $userId, int $folderId, FlowRequest $request)
    {
        try {
            $folder = App::getService('fileRepository')::findOneFolderById($folderId);

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

            if (FlowBasic::save($uploadPath, $config, $request)) {
                $connection = Db::$connection;
                $connection->beginTransaction();

                $folder = App::getService('fileRepository')::findOneFolderById($folderId);

                if ($folder === null || $folder->userId !== $userId) {
                    $connection->rollBack();

                    if ($folder === null) {
                        return new JSONResponse(Helper::showError('Папка не найдена'), 400);
                    }

                    return new JSONResponse(Helper::showError('Доступ запрещен'), 403);
                }

                $maxAttempts = 2;
                $origenFileName = $request->getFileName();
                $fileMimeType = $request->getFile()['type'];
                $fileSize = $request->getFile()['size'];

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

                $this->randomClearFolder($chunksTempFolder);

                return new JSONResponse([
                    'status' => 'success',
                    'message' => 'Файл успешно загружен',
                    'path' => $uploadPath
                ]);
            } else {
                return new JSONResponse([
                    'status' => 'continue',
                    'message' => 'Чанк получен, продолжается загрузка'
                ]);
            }
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

    private function randomClearFolder(string $folder): void
    {
        if (1 == mt_rand(1, 100)) {
            Uploader::pruneChunks($folder);
        }
    }
}
