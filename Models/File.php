<?php

namespace Models;

class File
{
    private ?int $id;
    private int $userId;
    private int $folderId;
    private string $serverName;
    private string $origenName;
    private string $mimeType;
    private int $size;

    /**
     * Конструктор класса File.
     *
     * @param int $userId Идентификатор пользователя.
     * @param int $folderId Идентификатор папки.
     * @param string $serverName Имя файла на сервере.
     * @param string $origenName Оригинальное имя файла.
     * @param string $mimeType MIME-тип файла.
     * @param int $size Размер файла в байтах.
     * @param int|null $id Идентификатор файла (необязательный параметр).
     */
    public function __construct(int $userId, int $folderId, string $serverName, string $origenName, string $mimeType, int $size, ?int $id = null)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->folderId = $folderId;
        $this->serverName = $serverName;
        $this->origenName = $origenName;
        $this->mimeType = $mimeType;
        $this->size = $size;
    }

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
}
