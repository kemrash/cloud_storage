<?php

namespace Core;

class Render
{
    private string $templateName;
    private array $renderData;

    public function __construct(string $templateName, array $renderData = [])
    {
        $this->templateName = $templateName;
        $this->renderData = $renderData;
    }

    public function getRender(): string
    {
        $fullPathFile = './Templates/' . $this->templateName;

        if (!isset($fullPathFile) || !$data = file_get_contents('./Templates/' . $this->templateName)) {
            throw new AppException(__CLASS__, "Файл {$this->templateName} не найден, или не удается его прочитать");
        }

        foreach ($this->renderData as $key => $value) {
            $data = str_replace('{{ ' . $key . ' }}', $value, $data);
        }

        return $data;
    }
}
