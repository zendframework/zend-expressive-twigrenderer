# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.5.0 - 2019-11-26

### Added

- [#60](https://github.com/zendframework/zend-expressive-twigrenderer/pull/60) adds compatibility with twig/twig `^3.0`.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.4.0 - 2019-06-12

### Added

- [#59](https://github.com/zendframework/zend-expressive-twigrenderer/pull/59) adds Environment configuration options for 'cache', 'debug' and 'strict_variables'.

- [#57](https://github.com/zendframework/zend-expressive-twigrenderer/pull/57) adds support for PHP 7.3.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.3.0 - 2019-01-23

### Added

- [#53](https://github.com/zendframework/zend-expressive-twigrenderer/pull/53) adds support for the `auto_reload` option to the `TwigEnvironmentFactory`.
  When provided, Twig will recompile a template whenever it detects the source
  code has changed. This option should be **disabled** in production.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.2.0 - 2018-11-19

### Added

- Nothing.

### Changed

- [#50](https://github.com/zendframework/zend-expressive-twigrenderer/pull/50) updates an exception message thrown in the `TwigExtensionFactory` to be more consistent with other exceptions raised in the component.

- [#51](https://github.com/zendframework/zend-expressive-twigrenderer/pull/51) updates the minimum support twig versions to `^1.34` and `^2.4`.

- [#51](https://github.com/zendframework/zend-expressive-twigrenderer/pull/51) modifies all functionality to use namespaced versions of all Twig classes.
  Users may still reference classes using the previously supported
  pseudo-namespaces, as they are class aliases for the namespaced versions.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.1.0 - 2018-04-09

### Added

- [#46](https://github.com/zendframework/zend-expressive-twigrenderer/pull/46)
  adds two new configuration options (under the `twig` top-level configuration
  key) for the `TwigEnvironment`:

  - `optimizations` allows enabling or disabling optimizations, and may be the
    integer `-1` (enable all optimizations) or `0` (disable optimizations); the
    default is `-1`.

  - `autoescape` allows specyfing the auto-escaping strategy to use, and may be
    one of html, js, css, url, html_attr, or false; the default is "html".

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.0.0 - 2018-03-15

### Added

- [#36](https://github.com/zendframework/zend-expressive-twigrenderer/pull/36) and
  [#42](https://github.com/zendframework/zend-expressive-twigrenderer/pull/42)
  add support for zend-expressive-template v2, zend-expressive-router v3, and
  zend-expressive-helpers v5.

- [#38](https://github.com/zendframework/zend-expressive-twigrenderer/pull/38)
  adds a `ConfigProvider` class with default service wiring and configuration
  for the component. It also updates `composer.json` to add
  `extra.zf.config-provider` configuration to notify zend-component-installer
  of the shipped `ConfigProvider` class, allowing the plugin to inject the
  `ConfigProvider` in your application configuration during initial
  installation.

### Changed

- [#43](https://github.com/zendframework/zend-expressive-twigrenderer/pull/43)
  adds a TwigExtensionFactory so the TwigExtension can be customized.

  If you have the ConfigProvider in your `config\config.php` file, this factory
  is added for you. If you don't use the ConfigProvider, you need to add this
  to your config:

  ```php
  'dependencies' => [
      'factories' => [
          TwigExtension::class => TwigExtensionFactory::class,
      ],
  ],
  ```

- [#36](https://github.com/zendframework/zend-expressive-twigrenderer/pull/36)
  updates all code to add scalar and return type hints, including nullable types
  and void types, wherever they make sense. As such, if you are extending any
  classes from this component, you may need to check any signatures you
  override.

- [#37](https://github.com/zendframework/zend-expressive-twigrenderer/pull/37)
  updates the package `ExceptionInterface` to extend from the
  `ExceptionInterface` provided in zend-expressive-template.

### Deprecated

- Nothing.

### Removed

- [#36](https://github.com/zendframework/zend-expressive-twigrenderer/pull/36)
  removes support for PHP versions prior to PHP 7.1.

- [#36](https://github.com/zendframework/zend-expressive-twigrenderer/pull/36)
  removes support for zend-expressive-template releases prior to v2.

- [#36](https://github.com/zendframework/zend-expressive-twigrenderer/pull/36)
  removes support for zend-expressive-router releases prior to v3.

- [#36](https://github.com/zendframework/zend-expressive-twigrenderer/pull/36)
  removes support for zend-expressive-helpers releases prior to v5.

### Fixed

- Nothing.

## 1.5.0 - 2017-08-12

### Added

- [#30](https://github.com/zendframework/zend-expressive-twigrenderer/pull/30)
  adds support for the PSR-11 Container Interface.

### Changed

- [#33](https://github.com/zendframework/zend-expressive-twigrenderer/pull/33)
  removes duplicate code.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.4.0 - 2017-03-14

### Added

- [#29](https://github.com/zendframework/zend-expressive-twigrenderer/pull/29)
  adds support for zend-expressive-helpers 4.0.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.3.0 - 2017-03-02

### Added

- [#28](https://github.com/zendframework/zend-expressive-twigrenderer/pull/28)
  adds support for Twig 2.1. If you upgrade and receive this version, please be
  aware that Twig 2.X no longer allows short name "aliases" for extensions. As
  an example, the following works in Twig 1.X:

  ```php
  $environment->getExtension('core');
  ```

  but does not work in Twig 2.X, where you would instead need to use the fully
  qualified class name:

  ```php
  $environment->getExtension(Twig_Extension_Core::class);
  ```

  As the latter notation has worked across both versions, you may want to
  consider making that change before upgrading, if you were using the older
  notation.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.2.1 - 2017-01-12

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#26](https://github.com/zendframework/zend-expressive-twigrenderer/pull/26) fixes
  the zend-expressive-router constrain in composer.json.

## 1.2.0 - 2017-01-11

### Added

- [#12](https://github.com/zendframework/zend-expressive-twigrenderer/pull/12)
  adds the ability to provide a default timezone to use with Twig. Provide it
  via the `twig.timezone` setting:

  ```php
  return [
      'twig' => [
          'timezone' => 'America/Chicago',
      ],
  ];
  ```

- [#15](https://github.com/zendframework/zend-expressive-twigrenderer/pull/15)
  extracts a new factory, `TwigEnvironmentFactory`, from the
  `TwigRendererFactory`. This new factory is now responsible for creating and
  configuring the `Twig_Environment` instance.

  While users may continue to use existing configuration, which omits
  registration of the `Twig_Environment` service with this new factory, doing so
  now emits a deprecation notice, indicating they should update their dependency
  configuration.

  This also means that users may override the `Twig_Environment` service to
  provide alternate instantiation of that class, or add delegator factories in
  order to further configure the Twig environment.

- [#23](https://github.com/zendframework/zend-expressive-twigrenderer/pull/23)
  adds the ability to provide Twig runtime loaders via configuration. These may
  be provided as either instances or service names, under the
  `twig.runtime_loaders` setting:

  ```php
  return [
      'twig' => [
          'runtime_loaders' => [
              // runtime loader service names or instances of
              // Twig_RuntimeLoaderInterface
          ],
      ],
  ];
  ```

- [#18](https://github.com/zendframework/zend-expressive-twigrenderer/pull/18)
  adds support for zend-expressive-helpers 3.0.

- [#18](https://github.com/zendframework/zend-expressive-twigrenderer/pull/18)
  adds support for zend-expressive-router 2.0.

- [#18](https://github.com/zendframework/zend-expressive-twigrenderer/pull/18)
  adds new parameters to the included `path()` extension. It now accepts the
  following arguments:

  ```twig
  {{ path(
        'route_name',
        {'route_param': 'substitution'},
        {'query_param': 'value'},
        'fragment',
        {'reuse_result_params': false}
  ) }}
  ```

  The new arguments are the query parameters, fragment, and router options.

### Deprecated

- Nothing.

### Removed

- This release removes support for PHP 5.5.

### Fixed

- [#19](https://github.com/zendframework/zend-expressive-twigrenderer/pull/19)
  fixes how the factories test for prior registration of an extension.
  Previously, they pulled the extension name using the extension's `getName()`
  method; however, as of Twig 1.26, that method is deprecated from
  `Twig_ExtensionInterface`, and no longer used internally. This package's
  factories now use the class name of the extension to perform the checks.

## 1.1.1 - 2016-02-01

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#11](https://github.com/zendframework/zend-expressive-twigrenderer/pull/11)
  updates the `TwigExtension` class to implement `Twig_Extension_GlobalsInterface`,
  which is required starting with Twig 1.23 for forwards-compatibility.

## 1.1.0 - 2016-01-21

### Added

- [#8](https://github.com/zendframework/zend-expressive-twigrenderer/pull/8)
  adds `zendframework/zend-expressive-helpers` as a dependency, in order to
  consume its `UrlHelper` and `ServerUrlHelper` implementations.

  Adds the `url` and `absolute_url` twig functions to generate
  absolute urls for a route and path.

- [#10](https://github.com/zendframework/zend-expressive-twigrenderer/pull/10)
  adds config globals to pass to all twig templates.

  ```php
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

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.0.1 - 2016-01-21

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#9](https://github.com/zendframework/zend-expressive-twigrenderer/pull/9)
  fixes a skipped test, and updates the behavior of `TwigRendererFactory` to
  raise an exception if the 'config' service is not an array or `ArrayObject`.

## 1.0.0 - 2015-12-07

First stable release.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.3.1 - 2015-12-03

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#5](https://github.com/zendframework/zend-expressive-twigrenderer/pull/5)
  fixes an issue in the TwigRendererFactory whereby it failed if the 'config'
  service returned an `ArrayObject`. It now validates that it has a usable
  config value, raising an exception when it does not.

## 0.3.0 - 2015-12-02

### Added

- [#1](https://github.com/zendframework/zend-expressive-twigrenderer/pull/1)
  adds the ability to inject additional Twig extensions via configuration. This
  can be done using the following configuration:

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
 ],
 ```

### Deprecated

- [#1](https://github.com/zendframework/zend-expressive-twigrenderer/pull/1)
  deprecates usage of the `cache_dir` and `assets_*` sub-keys under the
  `templates` top-level key, in favor of positioning them beneath a `twig`
  top-level key. As `templates` and `twig` values are merged, however, this
  change should not affect end-users.

### Removed

- Nothing.

### Fixed

- [#4](https://github.com/zendframework/zend-expressive-twigrenderer/pull/4)
  removes the dependency on zendframework/zend-expressive, and replaces it with
  zend-framework/zend-expressive-template and
  zendframework/zend-expressive-router.

## 0.2.1 - 2015-11-10

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#3](https://github.com/zendframework/zend-expressive-twigrenderer/pull/3)
  updates the `renderAssetUrl()` method of the `TwigExtension` to mask
  versioning if it's empty (while also allowing zero versions).

## 0.2.0 - 2015-10-20

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updated to zend-expressive RC1.
- Added branch-alias of dev-master to 1.0-dev.

## 0.1.0 - 2015-10-10

Initial release.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
