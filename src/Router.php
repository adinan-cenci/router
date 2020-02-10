<?php

namespace AdinanCenci\Router;

class Router 
{
    protected $defaultNamespace         = '';

    protected $request                  = null;

    protected $passParametersAsArray    = false;

    protected $parameters               = array();

    protected $before = array(
        'get'       => array(), 
        'post'      => array(), 
        'put'       => array(), 
        'delete'    => array(), 
        'options'   => array(), 
        'patch'     => array()
    );

    protected $routes = array(
        'get'       => array(), 
        'post'      => array(), 
        'put'       => array(), 
        'delete'    => array(), 
        'options'   => array(), 
        'patch'     => array()
    );

    protected $error404;

    protected $headRequest = false;

    /**
     * @param string|null $baseDirectory Path to the directory to be used 
     * in determining the URI. If no directory is informed, it will assume 
     * the running script's directory.
     */
    public function __construct($baseDirectory = null) 
    {
        // error 404 default function
        $this->error404 = function($uri) 
        {
            Router::header404();
            echo 'Page "'.$uri.'" not found';
        };

        $this->request = new Request($baseDirectory);
    }

    public function __destruct() 
    {
        if ($this->headRequest) {
            ob_end_clean();
        }
    }

    public function __get($var) 
    {
        if ($var == 'request') {
            return $this->request;
        }

        return null;
    }

    public function namespace($namespace) 
    {
        $this->defaultNamespace = $namespace;
        return $this;
    }

    public function passParametersAsArray($bool = true) 
    {
        $this->passParametersAsArray = $bool;
        return $this;
    }

    public function parameter($index, $alternative = null) 
    {
        return empty($this->parameters[$index]) ? $alternative : $this->parameters[$index];
    }

    /**
     * Associates route(s) and http method(s) to a middleware
     *
     * @param string $methods | separated http methods. Post, get, put etc. Optional.
     * @param string|array $patterns Regex pattern(s)
     * @param callable $callback A function or the name of one
     */
    public function before($methods = '*', $patterns = '', $callback = '') 
    {
        list($methods, $patterns, $callback) = $this->sortAddParams(func_get_args());

        $methods = explode('|', strtolower($methods));

        foreach ($methods as $method) {
            $this->before[$method][] = array(
                $patterns, 
                $callback
            );
        }

        return $this;
    }

    /**
     * Associates route(s) and http method(s) to a function/method
     *
     * @param string $methods | separated http methods. Post, get, put etc. Optional.
     * @param string|array $patterns Regex pattern(s)
     * @param callable $callback A function or the name of one
     */
    public function add($methods = '*', $patterns = '', $callback = '') 
    {
        list($methods, $patterns, $callback) = $this->sortAddParams(func_get_args());
        
        $methods = explode('|', strtolower($methods));

        foreach ($methods as $method) {
            $this->routes[$method][] = array(
                $patterns, 
                $callback
            );
        }

        return $this;
    }

    /** Shorthands for the ::add method */
    public function get($patterns, $callback) 
    {
        $this->add('get', $patterns, $callback);
        return $this;
    }

    public function post($patterns, $callback) 
    {
        $this->add('post', $patterns, $callback);
        return $this;
    }

    public function put($patterns, $callback) 
    {
        $this->add('put', $patterns, $callback);
        return $this;
    }

    public function delete($patterns, $callback) 
    {
        $this->add('delete', $patterns, $callback);
        return $this;
    }

    public function options($patterns, $callback) 
    {
        $this->add('options', $patterns, $callback);
        return $this;
    }

    public function patch($patterns, $callback) 
    {
        $this->add('patch', $patterns, $callback);
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

    public function run() 
    {
        $uri    = $this->request->uri;
        $method = $this->request->method;
        $found  = false;

        if ($method == 'head') {
            $this->headRequest = true;
            $method = 'get';
            ob_start();
        }

        //------------

        foreach ($this->before[$method] as $key => $ar) {

            list($patterns, $callback) = $ar;

            foreach ((array) $patterns as $pattern) {        
                if ($this->match($pattern, $uri, $params)) {
                    $this->parameters = $params;                    
                    $this->call($callback, $params);

                }
            }
        }

        //------------ 

        $this->parameters = array();
        foreach ($this->routes[$method] as $key => $ar) {

            list($patterns, $callback) = $ar;

            foreach ((array) $patterns as $pattern) {        
                if ($this->match($pattern, $uri, $params)) {
                    $this->parameters = $params;
                    $found = true;
                    break;        
                }
            }

            if (! $found) {
                continue;
            }

            $this->call($callback, $params);

            break;
        }

        if (! $found) {
            $this->notFound($uri);
        }
    }

    protected function match($pattern, $subject, &$matches) 
    {
        if (! preg_match($pattern, $subject, $mtchs, \PREG_OFFSET_CAPTURE)) {
            return false;
        }

        $matches = array();

        array_shift($mtchs);

        $mtchs = array_map('unserialize', array_unique(array_map('serialize', $mtchs)));

        foreach ($mtchs as $key => $v) {
            $matches[$key] = $v[0];
        }

        return true;
    }

    protected function call($callback, $params) 
    {
        $params = (array) $params;

        if (! is_string($callback)) { // function
            $this->callFunction($callback, $params);
            return;
        }

        /*----*/

        if (self::isFilePath($callback)) { // file

            if (! file_exists($callback)) {
                throw new \Exception('file '.$callback.' does not exist', 1);
                return;
            }

            $this->requireFile($callback, $params);
            return;
        }

        /*----*/    

        $callback = substr($callback, 0, 1) != '\\' ? $this->defaultNamespace.$callback : $callback;

        /*----*/

        if (!substr_count($callback, '::') and !function_exists($callback)) {
            throw new \Exception('function '.$callback.' is not defined', 1);
            return;
        }

        if (! substr_count($callback, '::')) { // name of a function
            $this->callFunction($callback, $params);
            return;
        }

        /*----*/

        list($controller, $method) = explode('::', $callback);

        if (! class_exists($controller)) {
            throw new \Exception('Class '.$controller.' not found', 1);
            return;          
        }

        /*----*/

        $reflMethod = new \ReflectionMethod($controller, $method);
        
        if ($reflMethod->isStatic() and $reflMethod->isPublic()) {
            $this->callFunction($callback, $params);
            return;
        }

        /*----*/

        $reflClass = new \ReflectionClass($controller);

        if ($reflClass->IsInstantiable() and $reflMethod->isPublic() and !$reflMethod->isStatic()) {
            $this->callFunction(array(new $controller, $method), $params);
            return;
        }

        throw new \Exception('Incapable of accessing '.$callback, 1);        
    }

    protected function requireFile($file, $parameters) 
    {
        require $file;
    }

    protected function callFunction($func, $params) 
    {
        if (! $this->passParametersAsArray) {
            return call_user_func_array($func, $params);
        }

        return call_user_func($func, $params);
    }

    protected function notFound($path) 
    {
        if ($this->error404) {
            $this->call($this->error404, array($path));
        }
    }

    protected function sortAddParams($params) 
    {
        $methods    = 'get|post|put|delete|options|patch';
        $patterns   = null;
        $callback   = null;
        $x          = 0;

        if ($params[0] == '*') { 
            $x++;
        } else if (!is_array($params[0]) && !self::isRegexPattern($params[0])) {
            $x++;
        }

        $patterns = $params[$x];
        $callback = $params[$x + 1];

        return array(
            $methods, 
            $patterns, 
            $callback
        );
    }

    public static function header404($replace = true, $responseCode = 404) 
    {
        header('HTTP/1.0 404 Not Found', $replace, $responseCode);
    }

    protected static function isRegexPattern($string) 
    {
        if (! is_string($string)) {
            return false;
        }

        return $string[0] == '/' || $string[0] == '#';
    }

    protected static function isFilePath($string) 
    {
        return preg_match('/\.[a-z]{3}$/', $string);
    }
}
