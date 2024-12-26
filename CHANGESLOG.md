# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 5.0.0 - 2024-12-26
### Added
- [Issue 2](https://github.com/adinan-cenci/router/issues/2): Refactorated most of the library, making use of interfaces, adding dependency injection to the router and improved the documentation.
- It is now possible to add custom types of controllers.

---

## 4.0.1 - 2023-01-08
### Fixed
- Fixed an issue with the dependencies in the `composer.json` files.

---

## 4.0.0 - 2023-01-08

The library was rewriten from scratch around the [PSR-15](https://www.php-fig.org/psr/psr-15/), [PSR-7](https://www.php-fig.org/psr/psr-7/) and [PSR-17](https://www.php-fig.org/psr/psr-17/) specifications.

### Changed

- All parameters of `::add()` and `::before()` are obligatory.

- The `$baseDirectory` passed to the constructor must be an absolute path in relation to the file system, not just te doc root.

- Now all controllers receive two parameters: an instance of `Psr\Http\Message\ServerRequestInterface` and `Psr\Http\Server\RequestHandlerInterface` respectively.

- `::setNamespace()` renamed to `::setDefaultNamespace()`.

### Removed

- `::set404()`

- `:passParametersAsArray()`

- `::header404()`

- `::parameter()`

### Added

- `::setExceptionHandler()`

- `::setNotFoundHandler()`

---

## 3.0.2 - 2021-11-13

## Fixed

- Routes were being attached to all HTTP methods instead of only the specidied ones.
- Files with extension longer than 3 characters were not being recognized as files.

---

## 3.0.1 - 2021-02-01

### Fixed

- `Router` was generating error when faced with a empty path.

---

## 3.0.0 - 2021-02-01

### Fixed

- The `Router::namespace()` method was using the "namespace" reserved word. Method replaced with `Router::setNamespace()`.



## 2.1.1 - 2020-02-10

### Fixed

- Trying to set up routers for options and patch methods were generating error.
- Tidying up the documentation and some grammar errors.

---

## 2.1.0 - 2020-02-03

### Fixed

- An error was occurring when omitting the first parameter of the of methods `::add()` and `::before()`.
- Only the first capture group was beign passed as a parameter to the callback.

### Added

- Now `::add` and `::before` accept file paths as callback, the file will be required if it exists and an exception will be thrown if it doesn't.
- Added the method `::passParametersAsArray()`. It changes how capture groups are passed to the callbacks.
  
  By default the capture groups in the regex patterns are passed to the callbacks as 
  individual parameters, if `::passParametersAsArray(true)` is called then the capture groups will be passed in single parameter as an array.
- Added the `::parameter()` method to retrieve captured groups.

---

## 2.0.0 - 2020-02-01

### Changed

- `Request::getRoute()` and `Request::$route` renamed to `Request::getUri()` and `Request::$uri` respectively.
- URIs no longer come with trailing slashes, as to avoid adding a `/?` at the end of the regex patterns.

### Added

- The ability to inform the base directory in the Router's constructor.

---

## 1.0.1 - 2019-10-14

Just tidying up the documentation and some grammar errors.

---

## 1.0.0 - 2019-10-13

### Removed

- `Route::setNamespace($namespace)`
- `Route::getUrl()`
- `Route::getPath()`
- `Route::getBaseHref()`

### Added

- `Router::namespace($namespace)`
- `Router::request` propriety.
- Added support for before route middlewares.
  - `Router::before($methods = '*', $patterns, $callback)`. Accept the same parameters as ::add() .
- Support for the head, patch and options http methods added.
  - `Router::options()` shorthand.
  - `Router::patch()` shorthand.

### Changed

- Route::add has been overloaded, not only the $methods parameter accepts a ''*'' 
  to represent all http methods, but it is now optional.

- Request logic has been moved to a new separated class: Request.

- Now the library also considers the x-http-method-override header to determine the http method used.

---

## 0.2.0 - 2018-02-03

### Changed

Now the router may call non-static methods by instantiating objects
A bug fixed: ::namespace() may be used to set the defaultnamespace for functions/methods.

---

## 0.1.1 - 2018-01-29

### Changed

A bug fixed: Router::getPath() used to come with the query string attatched. 
