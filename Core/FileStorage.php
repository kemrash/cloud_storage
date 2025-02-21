<?php

namespace Core;

class FileStorage
{
    private string $pathFilesStorage;

    /**
     * Конструктор класса FileStorage.
     * Инициализирует путь к хранилищу файлов, используя конфигурацию приложения.
     */
    public function __construct()
    {
        $this->pathFilesStorage = Config::getConfig('app.uploadFile.folderFileStorage');
    }

    /**
     * Удаляет файл с указанным именем из хранилища файлов.
     *
     * @param string $fileName Имя файла, который необходимо удалить.
     *
     * @return void
     */
    public function deleteFile(string $fileName): void
    {
        $fullPath = $this->pathFilesStorage . $fileName;

        if (file_exists($fullPath)) {
            if (!unlink($fullPath)) {
                Helper::writeLog("Ошибка при удалении файла {$fullPath}");
            }
        }
    }

    /**
     * Удаляет файлы из списка.
     *
     * Метод принимает массив файлов и удаляет каждый файл, вызывая метод deleteFile.
     * Если список файлов пуст, метод завершает выполнение без действий.
     *
     * @param array<string> $filesList Массив файлов для удаления. Каждый элемент массива должен содержать ключ 'serverName'.
     *
     * @return void
     */
    public function deleteFiles(array $filesList = []): void
    {
        if (count($filesList) === 0) {
            return;
        }

        foreach ($filesList as $file) {
            $this->deleteFile($file['serverName']);
        }
    }

    /**
     * Принудительно загружает файл с сервера.
     *
     * @param string $serverName Имя файла на сервере.
     * @param string $origenName Оригинальное имя файла для загрузки.
     * @param string $contentType Тип содержимого файла (по умолчанию 'application/octet-stream').
     * @return bool|null Возвращает false, если файл не существует или произошла ошибка чтения файла, иначе завершает выполнение скрипта.
     */
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
