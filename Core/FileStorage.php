<?php

namespace Core;

class FileStorage
{
    public static function saveFile(string $uploadDir, string $fileName, array $file): array
    {
        $maxFileSize = Config::getConfig('app.uploadFile.maxFileSize');

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if ($file['size'] > $maxFileSize) {
            return Helper::showError("Превышен максимальный размер: {$maxFileSize} байт");
        }

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . DIRECTORY_SEPARATOR . $fileName)) {
            return Helper::showError('Файл не загружен');
        }

        return ['status' => 'ok'];
    }

    public static function deleteFile(string $uploadDir, string $fileName): void
    {
        unlink($uploadDir . DIRECTORY_SEPARATOR . $fileName);
    }
}
