# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 0.3.1 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

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
