# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Removed

- Route::setNamespace($namespace)
- Route::getUrl()
- Route::getPath()
- Route::getBaseHref()

### Added

- Route::namespace($namespace)
- Route::request object.

### Changed

- Route::add now accepts a ''*'' to represent all http methods.

## 0.2.0 - 2018-02-03

### Changed
Now the router may call non-static methods by instantiating objects
A bug fixed: ::namespace() may be used to set the defaultnamespace for functions/methods.


## 0.1.1 - 2018-01-29

### Changed
A bug fixed: ::getPath() used to come with the query string attatched. 
