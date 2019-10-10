<?php

/**
 * In order to resolve the path, this class will take in account 
 * the parent directory of the SCRIPT_NAME in relation to DOCUMENT_ROOT
 */

namespace AdinanCenci\Router;

class Router 
{
    protected $defaultNamespace = '';
    protected $request = null;
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

        $this->request = new Request();
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

    /**
     * Associates ane or more path to a function/method and http method
     *
     * @param string $methods | separated http methods. Post, get, put etc.
     * @param string|array $patterns Regex pattern(s)
     * @param callable $callback A function or the name of one
     */
    public function add($methods = '*', $patterns, $callback) 
    {
        $methods = $methods == '*' ? 'get|post|put|delete' : $methods;
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

    /**
     * Sets a callback to handle error 404, that is
     * when no route matches the request
     */
    public function set404($callback) 
    {
        $this->error404 = $callback;
        return $this;
    }

    /**
     * Test all of the specified patterns and stops 
     * at the first match
     */
    public function run() 
    {
        $route  = $this->request->route;
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $found  = false;

        foreach ($this->routes[$method] as $key => $ar) {

            list($patterns, $callback) = $ar;

            foreach ((array) $patterns as $pattern) {        
                if (preg_match($pattern, $route, $matches)) {
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
            $this->notFound($route);
        }
    }

    protected function call($callback, $params) 
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

    public static function header404($replace = true, $responseCode = 404) 
    {
        header('HTTP/1.0 404 Not Found', $replace, $responseCode);
    }
}
