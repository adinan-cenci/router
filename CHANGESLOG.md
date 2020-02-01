# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 2.0.0 - 2020-??-??

### Changed

- Request::getRoute() and Request::$route renamed to Request::getUri() and Request::$uri respectively.
- URIs no longer come with trailing slashes, as to avoid adding a `/?` at the end of the regex patterns.

### Added

- The ability of inform the base directory xxxx



## 1.0.1 - 2019-10-14

Just tidying up the documentation and some grammar errors.

## 1.0.0 - 2019-10-13

### Removed

- Route::setNamespace($namespace)
- Route::getUrl()
- Route::getPath()
- Route::getBaseHref()

### Added

- Router::namespace($namespace)
- Router::request propriety.
- Added support for before route middlewares.
  - Router::before($methods = '*', $patterns, $callback). Accept the same parameters as ::add() .
- Support for the head, patch and options http methods added.
  - Router::options() shorthand.
  - Router::patch() shorthand.

### Changed

- Route::add has been overloaded, not only the $methods parameter accepts a ''*'' 
  to represent all http methods, but it is now optional.
- Request logic has been moved to a new separated class: Request.
- Now the library also considers the x-http-method-override header to determine the 
  http method used.

## 0.2.0 - 2018-02-03

### Changed
Now the router may call non-static methods by instantiating objects
A bug fixed: ::namespace() may be used to set the defaultnamespace for functions/methods.


## 0.1.1 - 2018-01-29

### Changed
A bug fixed: Router::getPath() used to come with the query string attatched. 
