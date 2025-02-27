<?php

namespace Models;

use Core\App;
use Core\Config;
use Core\Db;
use Exception;
use Flow\Basic;
use Flow\Request;
use Flow\Config as FlowConfig;
use Flow\Uploader;
use PDOException;
use Ramsey\Uuid\Uuid;

class File
{
    private const DB_NAME = 'file';
    private ?int $id;
    private int $userId;
    private int $folderId;
    private string $serverName;
    private string $origenName;
    private string $mimeType;
    private int $size;

    /**
     * Магический метод для получения значения свойства объекта.
     *
     * @param string $name Имя свойства, значение которого нужно получить.
     * @return string|int|null Значение свойства, если оно существует, или null, если свойство не найдено.
     */
    public function __get($name): string|int|null
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
    }

    /**
     * Возвращает список файлов для указанного пользователя.
     *
     * @param int $userId Идентификатор пользователя.
     * @return array<int, array<string, mixed>> Массив файлов, где каждый файл представлен в виде ассоциативного массива с ключами:
     * - 'id' (int): Идентификатор файла.
     * - 'folderId' (int): Идентификатор папки.
     * - 'serverName' (string): Имя файла на сервере.
     * - 'origenName' (string): Оригинальное имя файла.
     * - 'mimeType' (string): MIME-тип файла.
     * - 'size' (int): Размер файла в байтах.
     */
    public function list(int $userId): array
    {
        $data = Db::findBy(
            self::DB_NAME,
            ['id', 'folderId', 'serverName', 'origenName', 'mimeType', 'size'],
            Config::getConfig('database.dbColumns.file'),
            ['userId' => $userId]
        );

        return $data === null ? [] : $data;
    }

    /**
     * Получает данные файла из базы данных по заданным параметрам.
     *
     * @param array<string, mixed> $params Ассоциативный массив параметров для поиска файла.
     * 
     * @return bool Возвращает true, если файл найден и данные успешно загружены, иначе false.
     */
    public function get(array $params): bool
    {
        $data = Db::findOneBy(self::DB_NAME, $params, Config::getConfig('database.dbColumns.file'));

        if ($data === null) {
            return false;
        }

        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        return true;
    }

    /**
     * Получает файлы в указанной папке.
     *
     * @param int $folderId Идентификатор папки.
     * @return array<int, array<string, mixed>> Массив файлов, где каждый файл представлен в виде ассоциативного массива с ключами 'id' и 'origenName'.
     */
    public function getFilesInFolder(int $folderId): array
    {
        return Db::findBy(self::DB_NAME, ['id', 'origenName', 'serverName'], Config::getConfig('database.dbColumns.file'), ['folderId' => $folderId]);
    }

    /**
     * Добавляет чанки файла для указанного пользователя и папки.
     *
     * @param int $userId Идентификатор пользователя.
     * @param int $folderId Идентификатор папки.
     * @param Request $request Объект запроса, содержащий данные о загружаемом файле.
     * @return array{
     *     status: string,
     *     message: string,
     *     path?: string
     * } Массив с результатом выполнения операции. В случае успешной загрузки файла возвращается путь к файлу.
     * @throws Exception В случае возникновения ошибок при создании директорий, сохранении файла или генерации уникального имени файла.
     */
    public function addFileChunks(int $userId, int $folderId, Request $request): array
    {
        $config = new FlowConfig();
        $chunksTempFolder = './chunks_temp_folder';
        $uploadFolder = Config::getConfig('app.uploadFile.folderFileStorage');

        try {
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
                $maxAttempts = 2;
                $fileSize = filesize($uploadPath);
                $fileMimeType = mime_content_type($uploadPath);

                if ($fileMimeType === false) {
                    $fileMimeType = '';
                }

                $this->userId = $userId;
                $this->folderId = $folderId;
                $this->serverName = $uploadFileName;
                $this->origenName = $request->getFileName();
                $this->mimeType = $fileMimeType;
                $this->size = (int) $fileSize;

                for ($i = 0; $i <= $maxAttempts; $i++) {
                    try {
                        $this->addFile();
                        break;
                    } catch (PDOException $e) {
                        if ($e->getCode() !== '23000') {
                            throw new Exception($e->getMessage());
                        }
                    }

                    $oldFileName = $this->serverName;
                    $this->serverName = uniqid('', true) . "_" . Uuid::uuid4()->toString();

                    if ($i === $maxAttempts) {
                        throw new Exception("Не удалось сгенерировать уникальное имя файла после {$maxAttempts} попыток.");
                    }

                    try {
                        rename($uploadFolder . $oldFileName, $uploadFolder . $this->serverName);
                    } catch (Exception $e) {
                        throw new Exception($e->getMessage());
                    }
                }

                $this->randomClearFolderChunks($chunksTempFolder);

                return [
                    'status' => 'success',
                    'message' => 'Файл успешно загружен',
                    'path' => $uploadPath
                ];
            }

            return [
                'status' => 'continue',
                'message' => 'Чанк получен, продолжается загрузка'
            ];
        } catch (Exception $e) {
            if (isset($this->serverName) && file_exists($uploadFolder . $this->serverName)) {
                try {
                    unlink($uploadFolder . $this->serverName);
                } catch (Exception $e) {
                    throw new Exception(__CLASS__ . ': ' . $e->getMessage());
                }
            }

            throw new Exception(__CLASS__ . ': ' . $e->getMessage());
        }
    }

    /**
     * Добавляет файл в базу данных.
     *
     * Метод собирает информацию о файле в массив и вставляет его в базу данных.
     * После успешной вставки, идентификатор нового файла сохраняется в свойство $this->id.
     *
     * @return void
     */
    public function addFile(): void
    {
        $addFile = [
            'userId' => $this->userId,
            'folderId' => $this->folderId,
            'serverName' => $this->serverName,
            'origenName' => $this->origenName,
            'mimeType' => $this->mimeType,
            'size' => $this->size,
        ];

        $this->id = Db::insert(self::DB_NAME, $addFile, Config::getConfig('database.dbColumns.file'));
    }

    /**
     * Переименовывает файл.
     *
     * @param string $newName Новое имя файла.
     * @return bool Возвращает true, если переименование прошло успешно, иначе false.
     */
    public function rename(string $newName): bool
    {
        $data = Db::updateOneBy(self::DB_NAME, ['origenName' => $newName], ['id' => $this->id], Config::getConfig('database.dbColumns.file'));

        if (!isset($data) || isset($data['code']) && $data['code'] === '23000') {
            return false;
        }

        $this->origenName = $newName;

        return true;
    }

    /**
     * Удаляет файл из базы данных и файлового хранилища.
     *
     * Метод удаляет запись о файле из базы данных, используя идентификатор файла,
     * и затем удаляет сам файл из файлового хранилища.
     *
     * @return void
     */
    public function delete(): void
    {
        Db::deleteOneBy(self::DB_NAME, ['id' => $this->id], Config::getConfig('database.dbColumns.user'));
        App::getService('fileStorage')->deleteFile($this->serverName);
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
