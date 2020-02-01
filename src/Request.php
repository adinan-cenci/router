<?php
namespace AdinanCenci\Router;

class Request 
{
    protected $baseDirectory = '';

    /**
     * @param string|null $baseDirectory Path to the directory to be used 
     * in determining the URI. If no directory is informed, it will assume 
     * the running script's directory.
     */
    public function __construct($baseDirectory = null) 
    {
        $baseDirectory = $baseDirectory ? 
            $baseDirectory : 
            self::getRelativePathToScriptDirectory();

        $this->setBaseDirectory($baseDirectory);
    }

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
        return trim(preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']), '/');
    }

    public function getQuery() 
    {
        if (preg_match('/(\?.*)$/', $_SERVER['REQUEST_URI'], $matches)) {
            return ltrim($matches[1], '?');
        }

        return null;
    }

    public function getUrl($withQueryString = true) 
    {
        $path = $this->path;

        return 
        $this->scheme.'://'.
        $_SERVER['HTTP_HOST'].
        ( $path ? '/'.$path : '' ).
        ( $withQueryString ? '?'.$this->query : '' );        
    }

    /** 
     * Return the part of the path past the $baseDirectory.
     * 
     * Considere the example:
     * Your URL:         mywebsite.com/public/about
     * Your script is inside      /www/public/index.php
     * Your base directory is     /www/public/ 
     * ::getUri() would have returned 'about'
     * 
     * If the $baseDirectory were /www/
     * then ::getUri() would have returned 'public/about'
     */
    public function getUri() 
    {
        return trim(str_replace($this->baseDirectory, '', $this->path), '/');
    }

    public function getBaseHref() 
    {
        return rtrim(str_replace($this->uri, '', $this->getUrl(false)), '/').'/';
    }

    public function getHeaders() 
    {
        $headers = array();

        if (function_exists('getallheaders')) {
            $headers = \getallheaders();
        }

        if ($headers) {

            foreach ($headers as $key => $value) {
                $headers[strtolower($key)] = $value;
            }

            return $headers;
        }

        $headers = array();

        foreach ($_SERVER as $key => $value) {

            $key = strtolower($key);

            if (substr($key, 0, 5) != 'http_' && ($key != 'content_type') && $key != 'content_length') {
                continue;
            }

            $name = ltrim(str_replace('_', '-', $key), 'http-');

            $headers[$name] = $value;
        }

        return $headers;
    }

    public function getMethod() 
    {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        
        if ($method != 'post') {
            return $method;
        }

        $headers = $this->getHeaders();

        if (! isset($headers['x-http-method-override'])) {
            return $method;
        }

        $overriden = strtolower($headers['x-http-method-override']);

        if (in_array($overriden, array('put', 'delete', 'patch'))) {
            $method = $overriden;
        }

        return $method;
    }

    protected function setBaseDirectory($directory) 
    {
        $dir = self::forwardSlash($directory);

        if (! self::isRelativePath($dir)) {
            $dir = self::getRelativePathFromDocumentRoot($dir);
        }

        $this->baseDirectory = trim($dir, '/');
        return $this;
    }

    //------------

    protected static function getRelativePathToScriptDirectory() 
    {
        return trim( self::forwardSlash(dirname($_SERVER['SCRIPT_NAME'])), '/');
    }

    protected static function getRelativePathFromDocumentRoot($path) 
    {
        return trim( str_replace(self::getDocumentRoot(), '', $path), '/');
    }

    protected static function getDocumentRoot() 
    {
        return self::forwardSlash($_SERVER['DOCUMENT_ROOT']);
    }

    protected static function isRelativePath($path) 
    {
        $path = self::forwardSlash($path);


        if ($path[0] == '/') {
            return false;
        }

        return (bool) preg_match('#^[A-Z]:/#', $path);
    }

    protected static function forwardSlash($string) 
    {
        return str_replace('\\', '/', $string);
    }
}
