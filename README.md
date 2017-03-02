# Twig Integration for Expressive

[![Build Status](https://secure.travis-ci.org/zendframework/zend-expressive-twigrenderer.svg?branch=master)](https://secure.travis-ci.org/zendframework/zend-expressive-twigrenderer)
[![Coverage Status](https://coveralls.io/repos/zendframework/zend-expressive-twigrenderer/badge.svg?branch=master)](https://coveralls.io/r/zendframework/zend-expressive-twigrenderer?branch=master)

Provides [Twig](http://twig.sensiolabs.org/) integration for
[Expressive](https://docs.zendframework.com//zend-expressive/).

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
- [Aura.Di](https://github.com/auraphp/Aura.Di): `composer require aura/di`

## Twig Extension

The included Twig extension adds support for url generation. The extension is automatically activated if the
[UrlHelper](https://github.com/zendframework/zend-expressive-helpers#urlhelper) and
[ServerUrlHelper](https://github.com/zendframework/zend-expressive-helpers#serverurlhelper) 
are registered with the container.

- ``path``: Render the relative path for a given route and parameters. If there
  is no route, it returns the current path.

  ```twig
  {{ path('article_show', {'id': '3'}) }}
  Generates: /article/3
  ```
  
  ``path`` supports optional query parameters and a fragment identifier.

  ```twig
  {{ path('article_show', {'id': '3'}, {'foo': 'bar'}, 'fragment') }}
  Generates: /article/3?foo=bar#fragment
  ```

  By default the current route result is used where applicable. To disable this
  the `reuse_result_params` option can be set.

  ```twig
  {{ path('article_show', {}, {}, null, {'reuse_result_params': false}) }}
  ```

- ``url``: Render the absolute url for a given route and parameters. If there is
  no route, it returns the current url.

  ```twig
  {{ url('article_show', {'slug': 'article.slug'}) }}
  Generates: http://example.com/article/article.slug
  ```

  ``url`` also supports query parameters and a fragment identifier.

  ```twig
  {{ url('article_show', {'id': '3'}, {'foo': 'bar'}, 'fragment') }}
  Generates: http://example.com/article/3?foo=bar#fragment
  ```

  By default the current route result is used where applicable. To disable this
  the `reuse_result_params` option can be set.

  ```twig
  {{ url('article_show', {}, {}, null, {'reuse_result_params': false}) }}
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
    'runtime_loaders' => [
        // runtime loaders names or instances   
    ],
    'globals' => [
        // Global variables passed to twig templates
        'ga_tracking' => 'UA-XXXXX-X'
    ],
    'timezone' => 'default timezone identifier, e.g.: America/New_York',
],
```

## Documentation

See the Expressive [Twig documentation](https://docs.zendframework.com/zend-expressive/features/template/twig/).
