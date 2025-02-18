<?php

require_once './vendor/autoload.php';

function loader(string $className): void
{
    $fullFilePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';

    if (file_exists($fullFilePath)) {
        require_once $fullFilePath;
    }
}

spl_autoload_register('loader');
