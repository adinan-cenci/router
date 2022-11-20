<?php 
namespace AdinanCenci\Router\Helper;

abstract class File 
{
    public static function isRelativePath(string $path) : bool
    {
        if ($path == '') {
            return '';
        }

        return !preg_match('/^([A-Za-z]\:)?\//', $path);
    }

    /**
     * Ensures that the string contains a single trailing forward slash.
     * 
     * @param string $string
     * @return string
     */
    public static function trailingSlash(string $string) : string 
    {
        return rtrim($string, '/') . '/';
    }

    /**
     * Replace backward slashes for relative slashes.
     * 
     * @param string $string
     * @return string
     */
    public static function forwardSlash(string $string) : string
    {
        return str_replace('\\', '/', $string);
    }
}
