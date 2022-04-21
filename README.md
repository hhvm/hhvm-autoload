HHVM-Autoload
=============
The autoloader for autoloading classes, enums, functions, typedefs, and constants on HHVM.

[![Continuous Integration](https://github.com/hhvm/hhvm-autoload/actions/workflows/build-and-test.yml/badge.svg)](https://github.com/hhvm/hhvm-autoload/actions/workflows/build-and-test.yml)

Usage
=====

1. Add an `hh_autoload.json` file (see section below)
2. `composer require hhvm/hhvm-autoload`
3. Require the autoload file from your entrypoint functions using `require_once (__DIR__ . '(/..)(repeat as needed)/vendor/autoload.hack');`
4. Call `Facebook\AutoloadMap\initialize()` to register the autoloader with hhvm.
5. To re-generate the map, run `vendor/bin/hh-autoload`, `composer dump-autoload`, or any other command that generates the map

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
 - `"failureHandler:" classname<Facebook\AutoloadMap\FailureHandler>` - use the specified class to handle definitions that aren't the Map. Defaults to none.
 - `"devFailureHandler": classname<Facebook\AutoloadMap\FailureHandler>` - use a different handler for development environments. Defaults to the same value as `failureHandler`.
 - `"parser:" any of [ext-factparse]"` - select a parser to use, but there is only one valid option. Defaults to a sensible parser.
 - `"useFactsIfAvailable": false` - use ext-facts (HH\Facts\...) to back Facebook\AutoloadMap\Generated\map() instead of a codegenned dict. See _Use with HH\Facts_ for more information about this mode.

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

Use with HH\\Facts
=================

HHVM 4.109 introduced ext-facts and ext-watchman. Unlike the static pre-built autoloader which is built into a [repo authoratative](https://docs.hhvm.com/hhvm/advanced-usage/repo-authoritative) build, this native autoloader works incrementally and is suitable for autoloading in your development environment. For more information about setting up this autoloader, see the [blog post](https://hhvm.com/blog/2021/05/11/hhvm-4.109.html) for hhvm 4.109.

When using a native autoloader (either the repo auth or ext-facts autoloader), you do not need hhvm-autoload to require classes/functions/types/constants at runtime. If you (and your vendor dependencies) do not call any functions in the `Facebook\AutoloadMap` namespace, other than `Facebook\AutoloadMap\initialize()`, you don't need hhvm-autoload anymore. In that case, you could drop this dependency and remove the calls to `initialize()`. If you are using other functions, like `Facebook\AutoloadMap\Generated\map()`, you'd still need the vendor/autoload.hack file that hhvm-autoload generates.

Hhvm-autoload supports outputting a vendor/autoload.hack file which forwards all queries to ext-facts. `Facebook\AutoloadMap\Generated\map_uncached()` will always be up to date in this mode, since `HH\Facts` is always up to date. `Facebook\AutoloadMap\Generated\map()` is memoized (within a request), since some code may rely on getting the same result from multiple calls. You can enable this mode by adding `"useFactsIfAvailable": true` to the hh_autoload.json config file. Hhvm-autoload will emit a shim file instead of a full map. This option is ignored if `HH\Facts\enabled()` returns false, or when `--no-facts` is passed to `vendor/bin/hh-autoload`. We recommend passing `--no-facts` when building for production (specifically repo auth mode). Returning a hardcoded dict is faster than asking `HH\Facts`.

Important to note. Autoloading with a native autoloader does not respect hh_autoload.json. The repo auth autoloader allows any code to use any symbol. The facts autoloader honors the configuration in .hhvmconfig.hdf instead. Make sure that the configuration in hh_autoload.json and .hhvmconfig.hdf match.

How It Works
============

 - A parser (FactParse) provides a list of all Hack definitions in the specified locations
 - This is used to generate something similar to a classmap, except including other kinds of definitions
 - The map is provided to HHVM with [`HH\autoload_set_paths()`](https://docs.hhvm.com/hack/reference/function/HH.autoload_set_paths/)
 - If a native autoloader is registered, this autoloader will intentionally not register itself. So calling `Facebook\AutoloadMap\initialize()` in repo auth mode or when the facts based autoloader is registered is a noop.

Contributing
============

We welcome GitHub issues and pull requests - please see CONTRIBUTING.md for details.

License
=======

hhvm-autoload is [MIT-licensed](LICENSE).
