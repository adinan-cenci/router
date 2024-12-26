<?php

namespace AdinanCenci\Router\Helper;

abstract class Server
{
    /**
     * Returns the absolute path to the script being executed.
     *
     * @return string
     *   Absolute path to the file being executed.
     */
    public static function getCurrentFile(): string
    {
        return File::forwardSlash($_SERVER['SCRIPT_FILENAME']);
    }

    /**
     * Returns the relative path in relation to the site root.
     *
     * @param string $absolutePath
     *   Absolute path to a file/directory.
     *
     * @return string
     *   The relative path from the server root to $absolutePath.
     */
    public static function getRelativePathFromServerRoot(string $absolutePath): string
    {
        $root = self::getServerRoot();
        return str_replace($root, '', $absolutePath);
    }

    /**
     * Returns the absolute path to the server's root directory
     *
     * ( /var/www/, /home/username/public_html/ etc ).
     *
     * @return string
     *   The absolute path to the server root.
     */
    public static function getServerRoot(): string
    {
        return File::trailingSlash(File::forwardSlash($_SERVER['DOCUMENT_ROOT']));
    }
}
