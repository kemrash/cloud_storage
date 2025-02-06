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

    public function __construct(int $userId, int $folderId, string $serverName, string $origenName, string $mimeType, int $size, int $id = null)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->folderId = $folderId;
        $this->serverName = $serverName;
        $this->origenName = $origenName;
        $this->mimeType = $mimeType;
        $this->size = $size;
    }

    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
    }
}
