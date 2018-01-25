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

    /**
     * @param string $method Post, get, put etc.
     * @param string $pattern Regex pattern
     * @param callable $callback A function or the name of one
     */
    public function add($method, $pattern, $callback) 
    {
        $methods = explode('|', strtolower($method));

        foreach ($methods as $method) {
            $this->routes[$method][] = array(
                $pattern, 
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

    public function set404($callback) 
    {
        $this->error404 = $callback;
        return $this;
    }

    /**
     * Returns everything after the domain
     * @return string
     */
    public function getUri() 
    {
        $uri = trim($_SERVER['REQUEST_URI'], '/');
        $dir = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $uri = trim(str_replace($dir, '', $uri), '/');

        return $uri;
    }

    public function run() 
    {
        $uri    = $this->getUri();
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $found  = false;

        foreach ($this->routes[$method] as $key => $ar) {

            list($pattern, $callback) = $ar;
            
            if (! preg_match($pattern, $uri, $matches)) {
                continue;
            }

            $found = true;

            $params = isset($matches[1]) ? $matches[1] : array();

            $this->call($callback, $params);

            break;
        }

        if (! $found and $this->error404) {
            $this->call($this->error404, [$uri]);
        }
    }

    protected function call($callback, $params) 
    {
        if (is_string($callback)) {
            $callback = $this->defaultNamespace.$callback;
        }

        call_user_func_array($callback, $params);
    }
}
