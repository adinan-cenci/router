<?php
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
        $this->error404 = function() use ($r) 
        {
            $r->header404();
            echo 'Page not found';
        };
    }

    /**
     * Associates ane or more uri to a function/method and http method
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

    /**
     * Returns everything after the domain.
     * @return string
     */
    public function getPath() 
    {
        // trims off the script file's directory.
        $uri = trim($_SERVER['REQUEST_URI'], '/');
        $dir = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $uri = trim(str_replace($dir, '', $uri), '/');

        return $uri;
    }

    public function getUrl() 
    {
        return (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http' ).'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }

    public function header404() 
    {
        header('HTTP/1.0 404 Not Found');
    }

    /**
     * Test all of the specified patterns and stops 
     * at the first match
     */
    public function run() 
    {
        $uri    = $this->getPath();
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $found  = false;

        foreach ($this->routes[$method] as $key => $ar) {

            list($patterns, $callback) = $ar;

            foreach ((array) $patterns as $pattern) {        
                if (preg_match($pattern, $uri, $matches)) {
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
            $this->notFound($uri);
        }
    }

    protected function notFound($uri) 
    {
        if ($this->error404) {
            $this->call($this->error404, array($uri));
        }
    }

    protected function call($callback, $params) 
    {
        if (is_string($callback)) {
            $callback = $this->defaultNamespace.$callback;
        }

        call_user_func_array($callback, (array) $params);
    }
}
