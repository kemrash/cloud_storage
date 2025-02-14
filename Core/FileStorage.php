<?php

namespace Core;

use Exception;

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

    public function fileForceDownload(string $serverName, string $origenName, string $contentType = 'application/octet-stream'): ?bool
    {
        $fullPath = $this->pathFilesStorage . $serverName;

        if (!file_exists($fullPath)) {
            return false;
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header("Content-Type: {$contentType}");
        header("Content-Disposition: attachment; filename=\"" . rawurlencode($origenName) . "\"; filename*=UTF-8''" . rawurlencode($origenName));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fullPath));

        if (readfile($fullPath) === false) {
            Helper::writeLog(__CLASS__ . ': Ошибка чтения файла ' . $fullPath);

            return false;
        }

        exit;
    }
}
