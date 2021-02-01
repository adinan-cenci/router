<?php

namespace AdinanCenci\Router;

abstract class Helper 
{
    public static function getRelativePathToScriptDirectory() 
    {
        return trim( self::forwardSlash(dirname($_SERVER['SCRIPT_NAME'])), '/');
    }

    public static function getRelativePathFromDocumentRoot($path) 
    {
        return trim( str_replace(self::getDocumentRoot(), '', $path), '/');
    }

    public static function getDocumentRoot() 
    {
        return self::forwardSlash($_SERVER['DOCUMENT_ROOT']);
    }

    public static function isRelativePath($path) 
    {
        $path = self::forwardSlash($path);

        if ($path == '') {
            return true;
        }

        if ($path[0] == '/') {
            return false;
        }

        return (bool) preg_match('#^[A-Z]:/#', $path);
    }

    public static function forwardSlash($string) 
    {
        return str_replace('\\', '/', $string);
    }
}
