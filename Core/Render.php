<?php

namespace Core;

class Render
{
    private string $templateName;
    private array $renderData;

    /**
     * Конструктор класса Render.
     *
     * @param string $templateName Название шаблона.
     * @param array<string, string|int> $renderData Массив данных для рендеринга.
     */
    public function __construct(string $templateName, array $renderData = [])
    {
        $this->templateName = $templateName;
        $this->renderData = $renderData;
    }

    /**
     * Метод для получения отрендеренного шаблона.
     *
     * @return string Возвращает строку с отрендеренным шаблоном.
     * @throws AppException Если файл шаблона не найден или не удается его прочитать.
     */
    public function getRender(): string
    {
        $fullPathFile = './templates/' . $this->templateName;

        if (!isset($fullPathFile) || !$data = file_get_contents($fullPathFile)) {
            throw new AppException(__CLASS__, "Файл {$this->templateName} не найден, или не удается его прочитать");
        }

        foreach ($this->renderData as $key => $value) {
            $data = str_replace('{{ ' . $key . ' }}', $value, $data);
        }

        return $data;
    }
}
