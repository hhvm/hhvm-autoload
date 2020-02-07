HHVM-Autoload [![Build Status](https://travis-ci.org/hhvm/hhvm-autoload.svg?branch=master)](https://travis-ci.org/hhvm/hhvm-autoload)
=============
The autoloader for autoloading classes, enums, functions, typedefs, and constants on HHVM.

Usage
=====

1. Add an `hh_autoload.json` file (see section below) and optionally remove your configuration from composer.json
2. `composer require hhvm/hhvm-autoload`
3. Replace any references to `vendor/autoload.php` with  `vendor/autoload.hack` and call `Facebook\AutoloadMap\initialize()`
4. To re-generate the map, run `vendor/bin/hh-autoload`, `composer dump-autoload`, or any other command that generates the map

Configuration (`hh_autoload.json`)
==================================

A minimal configuration file is:

```JSON
{
  "roots": [ "src/" ]
}
```

This will look for autoloadable definitions in `src/`, and also look in `vendor/`.
Projects in `vendor/` are only processed if they also contain a `hh_autoload.json` file.

Previously we also supported projects without `hh_autoload.json` by simulating Composer's autoload behavior, but we no longer do because that mostly applied to PHP files which HHVM can no longer parse.

The following settings are optional:

 - `"extraFiles": ["file1.hack"]` - files that should not be autoloaded, but should be `require()`ed by `vendor/autoload.hack`. This should be needed much less frequently than under Composer
 - `"includeVendor": false` - do not include `vendor/` definitions in `vendor/autoload.hack`
 - `"devRoots": [ "path/", ...]` - additional roots to only include in dev mode, not when installed as a dependency.
 - `"relativeAutoloadRoot": false` - do not use a path relative to `__DIR__` for autoloading. Instead, use the path to the folder containing `hh_autoload.json` when building the autoload map.
 - `"failureHandler:" classname<Facebook\AutoloadMap\FailureHandler>` - use the specified class to handle definitions that aren't the Map. Your handler will not be invoked for functions or constants
   that aren't in the autoload map and have the same name as a definition in the global namespace. Defaults to none.
 - `"devFailureHandler": classname<Facebook\AutoloadMap\FailureHandler>` - use a different handler for development environments. Defaults to the same value as `failureHandler`.

Use In Development (Failure Handlers)
=====================================

When you add, remove, or move definitions, there are several options available:

 - run `composer dump-autoload` to regenerate the map
 - run `vendor/bin/hh-autoload` to regenerate the map faster
 - specify `devFailureHandler` as `Facebook\AutoloadMap\HHClientFallbackHandler`
 - specify a custom subclass of `Facebook\AutoloadMap\FailureHandler`
 - use a filesystem monitor such as
   [watchman](https://facebook.github.io/watchman/) to invoke one of the above
   commands when necessary

`Facebook\AutoloadMap\HHClientFallbackHandler` is probably the most
convenient for Hack development.

For performance reasons, failure handler methods will not be invoked for
namespaced functions or constants that have the same name as one in the
global namespace. You will need to re-generate the map if you make changes
to functions or constants that are affected by this restriction.

HHClientFallbackHandler
-----------------------

If you are working in Hack, this handler will remove the need to manually
rebuild the map in almost all circumstances.

It asks `hh_client` for definitions that aren't in the map, and has the
following additional behaviors:

 - it is disabled if the `CI`, `CONTINUOUS_INTEGRATION`, or `TRAVIS`
   environment variables are set to a Truthy value; this is because it
   is not a recommended approach for production environments, and you
   probably want your automated testing environment to reflect
   production
 - results are cached in both APC and a file in vendor/, if vendor/ is
   writable

You can override these behaviors in a subclass.

Custom Handlers
---------------

Information you may need is available from:

 - `Facebook\AutoloadMap\Generated\build_id()`: this is unique ID
    regenerated every time the map is rebuilt; it includes the date,
    time, and a long random hex string. If your failure handler has a
    cache, it most likely should be invalidated when this changes, for
    example, by including it in the cache key.
 - `Facebook\AutoloadMap\Generated\map()`: the autoload map
 - `Facebook\AutoloadMap\Generated\root()`: the directory containing the
    project root, i.e. the parent directory of `vendor/`

How It Works
============

 - A parser (FactParse) provides a list of all Hack definitions in the specified locations
 - This is used to generate something similar to a classmap, except including other kinds of definitions
 - The map is provided to HHVM with [`HH\autoload_set_paths()`](https://docs.hhvm.com/hack/reference/function/HH.autoload_set_paths/)

Contributing
============

We welcome GitHub issues and pull requests - please see CONTRIBUTING.md for details.

License
=======

hhvm-autoload is [MIT-licensed](LICENSE).
