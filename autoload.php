<?php

require_once './vendor/autoload.php';

function searchFile(string $folderPath, string $fileName, string $searchFolder, ?string &$searchPath = null): void
{
    $dirContents = scandir($folderPath);

    if ($dirContents === false) {
        return;
    }

    foreach ($dirContents as $content) {
        $path = $folderPath . DIRECTORY_SEPARATOR . $content;

        if ($searchPath !== null) {
            return;
        }

        if (is_dir($path) && $content !== '.' && $content !== '..') {
            searchFile($path, $fileName, $searchFolder, $searchPath);
        }

        if ($content === $fileName && basename($folderPath) === $searchFolder) {
            $searchPath = $path;
        }
    }
}

function loader(string $className): void
{
    $searchPath = null;
    $pathArray = explode('\\', $className);
    $pathArrayCount = count($pathArray);
    $fileName = $pathArray[$pathArrayCount - 1] . '.php';
    $searchFolder = $pathArray[$pathArrayCount - 2];

    searchFile(__DIR__, $fileName, $searchFolder, $searchPath);

    if ($searchPath !== null) {
        require_once $searchPath;
    }
}

spl_autoload_register('loader');
