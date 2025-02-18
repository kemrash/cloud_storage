<?php

namespace Models;

class Folder
{
    private ?int $id;
    private int $userId;
    private int $parentId;
    private string $name;

    /**
     * Конструктор класса Folder.
     *
     * @param int $userId Идентификатор пользователя.
     * @param int $parentId Идентификатор родительской папки.
     * @param string $name Название папки.
     * @param int|null $id Идентификатор папки (необязательный параметр).
     */
    public function __construct(int $userId, int $parentId, string $name, ?int $id = null)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->parentId = $parentId;
        $this->name = $name;
    }

    /**
     * Магический метод для получения значения свойства объекта.
     *
     * @param string $name Имя свойства, значение которого нужно получить.
     * @return string|int|null Значение свойства, если оно установлено, иначе null.
     */
    public function __get($name): string|int|null
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
    }
}
