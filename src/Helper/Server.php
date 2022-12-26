<?php 
namespace AdinanCenci\Router\Helper;

abstract class Server 
{
    /**
     * Return the absolute path to the script being executed.
     * 
     * @return string Absolute path
     */
    public static function getCurrentFile() : string 
    {
        return File::forwardSlash($_SERVER['SCRIPT_FILENAME']);
    }

    /**
     * Return the relative path in relation to the site root.
     * 
     * @param string $absolutePath
     * @return string The relative path
     */
    public static function getRelativePathFromServerRoot(string $absolutePath) : string
    {
        $root = self::getServerRoot();
        return str_replace($root, '', $absolutePath);
    }

    /**
     * Returns the absolute path to the server's root directory
     * ( /var/www/, /home/username/public_html/ etc ).
     * 
     * @return string
     */
    public static function getServerRoot() : string 
    {
        return File::trailingSlash(File::forwardSlash($_SERVER['DOCUMENT_ROOT']));
    }
}
