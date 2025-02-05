<?php

namespace Models;

class Folder
{
    private int $id;
    private int $userId;
    private int $parentId;
    private string $name;

    public function __construct(int $id, int $userId, int $parentId, string $name)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->parentId = $parentId;
        $this->name = $name;
    }

    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
    }
}
