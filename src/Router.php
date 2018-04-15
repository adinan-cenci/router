<?php

/**
 * In order to resolve the path, this class will take in account 
 * the parent directory of the SCRIPT_NAME in relation to DOCUMENT_ROOT
 */

namespace AdinanCenci\Router;

class Router 
{
    protected $defaultNamespace = '';

    protected $routes = array(
        'get'       => array(), 
        'post'      => array(), 
        'put'       => array(), 
        'delete'    => array()
    );

    protected $error404;

    public function __construct() 
    {
        // error 404 default function
        $r = $this;
        $this->error404 = function() 
        {
            self::header404();
            echo 'Page not found';
        };
    }

    public function setNamespace($namespace) 
    {
        $this->defaultNamespace = $namespace;
        return $this;
    }

    /**
     * Associates ane or more path to a function/method and http method
     *
     * @param string $methods | separated http methods. Post, get, put etc.
     * @param string|array $patterns Regex pattern(s)
     * @param callable $callback A function or the name of one
     */
    public function add($methods, $patterns, $callback) 
    {
        $methods = explode('|', strtolower($methods));

        foreach ($methods as $method) {
            $this->routes[$method][] = array(
                $patterns, 
                $callback
            );
        }

        return $this;
    }

    /** Shortcuts for ::add */
    public function get($pattern, $callback) 
    {
        $this->add('get', $pattern, $callback);
        return $this;
    }

    public function post($pattern, $callback) 
    {
        $this->add('post', $pattern, $callback);
        return $this;
    }

    public function put($pattern, $callback) 
    {
        $this->add('put', $pattern, $callback);
        return $this;
    }

    public function delete($pattern, $callback) 
    {
        $this->add('delete', $pattern, $callback);
        return $this;
    }

    /**
     * Sets a callback to handle error 404, that is
     * when no route matches the request
     */
    public function set404($callback) 
    {
        $this->error404 = $callback;
        return $this;
    }

    public function getUrl($queryString = true) 
    {
        $url = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http' ).'://'.$_SERVER['HTTP_HOST'];
        
        if ($queryString) {
            $url .= $_SERVER['REQUEST_URI'];
        } else {
            $url .= $this->getRequestUri();
        }
        
        return $url;
    }

    /**
     * Returns the path part of the url. That is, in relation to 
     * the SCRIPT_NAME's directory
     * @return string
     */
    public function getPath() 
    {
        // trims off the script file's directory
        $removeDir      = $this->scriptParentDirectory();
        $fromRequestUri = $this->getRequestUri();
        return trim(str_replace($removeDir, '', $fromRequestUri), '/');
    }

    public function getBaseHref() 
    {
        return rtrim(str_replace($this->getPath(), '', $this->getUrl(false)), '/').'/';
    }

    /**
     * Test all of the specified patterns and stops 
     * at the first match
     */
    public function run() 
    {
        $path   = $this->getPath();
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $found  = false;

        foreach ($this->routes[$method] as $key => $ar) {

            list($patterns, $callback) = $ar;

            foreach ((array) $patterns as $pattern) {        
                if (preg_match($pattern, $path, $matches)) {
                    $found = true;
                    break;        
                }
            }

            if (! $found) {
                continue;
            }            

            $params = isset($matches[1]) ? $matches[1] : array();

            $this->call($callback, $params);

            break;
        }

        if (! $found) {
            $this->notFound($path);
        }
    }

    public function call($callback, $params) 
    {
        $params = (array) $params;

        if (! is_string($callback)) {
            call_user_func_array($callback, $params);
            return;
        }

        /*----*/    

        $callback = substr($callback, 0, 1) != '\\' ? $this->defaultNamespace.$callback : $callback;

        /*----*/

        if (!substr_count($callback, '::') and !function_exists($callback)) {
            throw new \Exception('function '.$callback.' is not defined', 1);            
        }

        if (! substr_count($callback, '::')) {
            call_user_func_array($callback, $params);
            return;
        }

        /*----*/

        list($controller, $method) = explode('::', $callback);

        if (! class_exists($controller)) {
            throw new \Exception('Class '.$controller.' not found', 1);            
        }

        /*----*/

        $reflMethod = new \ReflectionMethod($controller, $method);
        
        if ($reflMethod->isStatic() and $reflMethod->isPublic()) {
            call_user_func_array($callback, $params);
            return;
        }

        /*----*/

        $reflClass = new \ReflectionClass($controller);

        if ($reflClass->IsInstantiable() and $reflMethod->isPublic() and !$reflMethod->isStatic()) {
            call_user_func_array([new $controller, $method], $params);
            return;
        }

        throw new \Exception('Incapable of accessing '.$callback, 1);        
    }

    protected function notFound($path) 
    {
        if ($this->error404) {
            $this->call($this->error404, array($path));
        }
    }

    /** 
     * gets the REQUEST_URI without the query string
     * @assert 'http://mywebsite.com.br/about/?who=us' == about
    */
    protected function getRequestUri() 
    {
        return preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
    }

    /** 
     * Returns the relative path to the directory 
     * of the executing script ( in relation to DOCUMENT_ROOT )
     * @return string
     */
    protected function scriptParentDirectory() 
    {
        return trim(dirname($this->forwardSlash($_SERVER['SCRIPT_NAME'])), '/');
    }

    protected function forwardSlash($string) 
    {
        return str_replace('\\', '/', $string);
    }

    public static function header404($replace = true, $responseCode = 404) 
    {
        header('HTTP/1.0 404 Not Found', $replace, $responseCode);
    }
}
