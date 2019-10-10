<?php

namespace AdinanCenci\Router;

class Request 
{
    public function __get($var)
    {
        $methodName = 'get'.ucfirst($var);

        if (method_exists($this, $methodName)) {
            return call_user_func(array($this, $methodName));
        }

        return null;
    }

    public function getScheme() 
    {
        return isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
    }

    public function getPath() 
    {
        return preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
    }

    public function getQuery() 
    {
        if (preg_match('/(\?.*)$/', $_SERVER['REQUEST_URI'], $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function getUrl($withQueryString = true) 
    {
        return 
        $this->scheme.'://'.
        $_SERVER['HTTP_HOST'].
        $this->path.
        ( $withQueryString ? $this->query : '' );        
    }

    /**
     * It will return the url's path minus the script's directory.
     * For example:
     * If the script is running in 'public_html/sub-folder/index.php' and the 
     * request url is 'https://foobar.com/sub-folder/user/noobmaster/'
     * Then the method will return 'user/noobmaster'
     * @return string
     */
    public function getRoute() 
    {
        // trims off the script file's directory
        return trim(str_replace($this->scriptDirectory, '', $this->path), '/');
    }

    public function getBaseHref() 
    {
        return rtrim(str_replace($this->route, '', $this->getUrl(false)), '/').'/';
    }

    /** 
     * Returns the relative path to SCRIPT_NAME in relation to DOCUMENT_ROOT.
     * Example:
     * DOCUMENT_ROOT = '/var/www/' and 
     * SCRIPT_NAME   = '/var/www/foo/bar/index.php' 
     * Then the method will return 'foo/bar'
     * @return string
     */
    protected function getScriptDirectory() 
    {
        return trim(dirname($this->forwardSlash($_SERVER['SCRIPT_NAME'])), '/');
    }

    protected function forwardSlash($string) 
    {
        return str_replace('\\', '/', $string);
    }
}
