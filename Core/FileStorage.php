<?php

namespace Core;

class FileStorage
{
    private string $pathFilesStorage;

    public function __construct()
    {
        $this->pathFilesStorage = Config::getConfig('app.uploadFile.folderFileStorage');
    }

    public function deleteFile(string $fileName): void
    {
        $fullPath = $this->pathFilesStorage . $fileName;

        if (file_exists($fullPath)) {
            if (!unlink($fullPath)) {
                Helper::writeLog("Ошибка при удалении файла {$fullPath}");
            }
        }
    }

    public function deleteFiles(array $filesList = []): void
    {
        if (count($filesList) === 0) {
            return;
        }

        foreach ($filesList as $file) {
            $this->deleteFile($file['serverName']);
        }
    }
}
