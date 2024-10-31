<?php

define('ROOT_PATH', dirname(__DIR__));

function addDirectory(string $dirname, ZipArchive $zip)
{
    /* @var SplFileInfo[] $files */
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(ROOT_PATH . '/' . $dirname),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file)
    {
        // Skip directories (they would be added automatically)
        if (!$file->isDir())
        {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen(ROOT_PATH) + 1);

            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
        }
    }
}

$xml = simplexml_load_file(ROOT_PATH . '/dpe.xml');
$zip = new ZipArchive();
$zip->open(ROOT_PATH . '/dist/plg_jea-' . $xml->version . '.zip', ZipArchive::CREATE|ZipArchive::OVERWRITE);
addDirectory('language', $zip);
addDirectory('vendor', $zip);
$zip->addFile(ROOT_PATH . '/dpe.xml', 'dpe.xml');
$zip->addFile(ROOT_PATH . '/dpe.php', 'dpe.php');
$zip->addFile(ROOT_PATH . '/script.php', 'script.php');

$zip->close();
