<?php

namespace AdinanCenci\Router\Helper;

abstract class File
{
    /**
     * Returns the parent directory of a given path.
     *
     * @param string $path
     *   Path to a file/directory.
     *
     * @return string
     *   The parent directory.
     */
    public static function getParentDirectory(string $path): string
    {
        return File::trailingSlash(dirname($path));
    }

    /**
     * Checks if $path is relative.
     *
     * @param string $path
     *   Path to a file/directory.
     *
     * @return bool
     *   True if it is relative.
     */
    public static function isRelativePath(string $path): bool
    {
        if ($path == '') {
            return false;
        }

        return !preg_match('/^([A-Za-z]\:)?\//', $path);
    }

    /**
     * Ensures that the string contains a single trailing forward slash.
     *
     * @param string $string
     *   The string.
     *
     * @return string
     *   The string with the appended /.
     */
    public static function trailingSlash(string $string): string
    {
        return rtrim($string, '/') . '/';
    }

    /**
     * Replace backward slashes for forward slashes.
     *
     * @param string $string
     *   The string.
     *
     * @return string
     *   The string with forward slashes.
     */
    public static function forwardSlash(string $string): string
    {
        return str_replace('\\', '/', $string);
    }
}
