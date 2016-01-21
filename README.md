# Twig Integration for Expressive

[![Build Status](https://secure.travis-ci.org/zendframework/zend-expressive-twigrenderer.svg?branch=master)](https://secure.travis-ci.org/zendframework/zend-expressive-twigrenderer)

Provides [Twig](http://twig.sensiolabs.org/) integration for
[Expressive](https://github.com/zendframework/zend-expressive).

## Installation

Install this library using composer:

```bash
$ composer require zendframework/zend-expressive-twigrenderer
```
We recommend using a dependency injection container, and typehint against
[container-interop](https://github.com/container-interop/container-interop). We
can recommend the following implementations:

- [zend-servicemanager](https://github.com/zendframework/zend-servicemanager):
  `composer require zendframework/zend-servicemanager`
- [pimple-interop](https://github.com/moufmouf/pimple-interop):
  `composer require mouf/pimple-interop`
- [Aura.Di](https://github.com/auraphp/Aura.Di)

## Twig Extension

The included Twig extension adds support for url generation. The extension is automatically activated if the
[UrlHelper](https://github.com/zendframework/zend-expressive-helpers#urlhelper) and
[ServerUrlHelper](https://github.com/zendframework/zend-expressive-helpers#serverurlhelper) are registered with the
container.

- ``path``: Render the relative path for a given route and parameters. If there
  is no route, it returns the current path.

  ```twig
  {{ path('article_show', {'id': '3'}) }}
  Generates: /article/3
  ```

- ``url``: Render the absolute url for a given route and parameters. If there is
  no route, it returns the current url.

  ```twig
  {{ url('article_show', {'slug': 'article.slug'}) }}
  Generates: http://example.com/article/article.slug
  ```

- ``absolute_url``: Render the absolute url from a given path. If the path is
  empty, it returns the current url.

  ```twig
  {{ absolute_url('path/to/something') }}
  Generates: http://example.com/path/to/something
  ```

- ``asset`` Render an (optionally versioned) asset url.

  ```twig
  {{ asset('path/to/asset/name.ext', version=3) }}
  Generates: path/to/asset/name.ext?v=3
  ```

  To get the absolute url for an asset:

  ```twig
  {{ absolute_url(asset('path/to/asset/name.ext', version=3)) }}
  Generates: http://example.com/path/to/asset/name.ext?v=3
  ```

## Configuration

```php
'templates' => [
    'extension' => 'file extension used by templates; defaults to html.twig',
    'paths' => [
        // namespace / path pairs
        //
        // Numeric namespaces imply the default/main namespace. Paths may be
        // strings or arrays of string paths to associate with the namespace.
    ],
],
'twig' => [
    'cache_dir' => 'path to cached templates',
    'assets_url' => 'base URL for assets',
    'assets_version' => 'base version for assets',
    'extensions' => [
        // extension service names or instances
    ],
    'globals' => [
        // Global variables passed to twig templates
        'ga_tracking' => 'UA-XXXXX-X'
    ],
],
```

## Documentation

See the [zend-expressive](https://github.com/zendframework/zend-expressive/blob/master/doc/book)
documentation tree, or browse online at http://zend-expressive.rtfd.org.
