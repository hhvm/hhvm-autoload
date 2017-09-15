HHVM-Autoload [![Build Status](https://travis-ci.org/hhvm/hhvm-autoload.svg?branch=master)](https://travis-ci.org/hhvm/hhvm-autoload)
=============
A Composer plugin for autoloading classes, enums, functions, typedefs, and constants on HHVM.

FAQ
===

Do I need to use Hack?
----------------------

No, PHP is fine - but HHVM is required because:

 - PHP does not support autoloading anything other than classes
 - this project and the parser are written in Hack

Can I autoload functions and constants if I'm not writing Hack?
---------------------------------------------------------------

Yes :)

Why does this project use Composer's autoloader?
------------------------------------------------

It can't depend on itself :)

Usage
=====

1. Add an `hh_autoload.json` file (see section below) and optionally remove your configuration from composer.json
2. `composer require facebook/hhvm-autoload`
3. Replace any references to `vendor/autoload.php` with  `vendor/hh_autoload.php`
4. If you are using PHPUnit, you will need to add `vendor/hh_autoload.php` to your `bootstrap.php`, or to `phpunit.xml` as a `bootstrap` file if you don't already have one. This is because PHPUnit automatically loads `vendor/autoload.php`, but is not aware of `vendor/hh_autoload.php`
5. To re-generate the map, run `vendor/bin/hh-autoload`, `composer dump-autoload`, or any other command that generates the map

Configuration (`hh_autoload.json`)
==================================

A minimal configuration file is:

```JSON
{
  "roots": [ "src/" ]
}
```

This will look for autoloadable definitions in `src/`, and also look in `vendor/`. It will pay attention to the `autoload` sections of `composer.json` inside the `vendor/` directory.

The following settings are optional:

 - `"extraFiles": ["file1.php"]` - files that should not be autoloaded, but should be `require()`ed by `vendor/hh_autoload.php`. This should be needed much less frequently than under Composer
 - `"includeVendor": false` - do not include `vendor/` definitions in `vendor/hh_autoload.php`
 - `"autoloadFilesBehavior": "scan"|"exec"` - whether autoload `files` from vendor should be `scan`ned for definitions, or `exec`uted by `vendor/hh_autoload.php` - `scan` is the default, and generally favorable, but `exec` is needed if you have dependencies that need code to be executed on startup. `scan` is sufficient if your dependencies just use `files` because they need to define things that aren't classes, which is usually the case.
 - `"parser": "ext-factparse"|"definition-finder"` - how to parse files. FactParse is an HHVM extension in 3.18 and above, while DefinitionFinder is a library supporting older versions of HHVM.
 - `"devRoots": [ "path/", ...]` - additional roots to only include in dev mode, not when installed as a dependency.
 - `"relativeAutoloadRoot": false` - do not use a path relative to `__DIR__` for autoloading. Instead, use the path to the folder containing `hh_autoload.json` when building the autoload map.
 - `"failureHandler:" classname<Facebook\AutoloadMap\FailureHandler>` - use the specified class to handle definitions that aren't the Map. Your handler will not be invoked for functions or constants
   that aren't in the autoload map and have the same name as a definition in the global namespace. Defaults to none.
 - `"devFailureHandler": classname<Facebook\AutoloadMap\FailureHandler>` - use a different handler for development environments. Defaults to the same value as `failureHandler`.

Development Workflow
====================

When you add, remove, or move definitions, there are several options available:

 - run `composer dump-autoload` to regenerate the map
 - run `vendor/bin/hh-autoload` to regenerate the map faster
 - specify `devFailureHandler` as `Facebook\AutoloadMap\HHClientFallbackHandler`
 - specify a custom subclass of `Facebook\AutoloadMap\FailureHandler`
 - use a filesystem monitor such as
   [watchman](https://facebook.github.io/watchman/) to invoke one of the above
   commands when necessary

How It Works
============

 - A parser (FactParse or DefinitionFinder) provides a list of all PHP and Hack definitions in the specified locations
 - This is used to generate something similar to a classmap, except including other kinds of definitions
 - The map is provided to HHVM with [`HH\autoload_set_paths()`](https://docs.hhvm.com/hack/reference/function/HH.autoload_set_paths/)

The [Composer plugin API](https://getcomposer.org/doc/articles/plugins.md) allows it to re-generate the `vendor/hh_autoload.php` file automatically whenever Composer itself regenerates `vendor/autoload.php`

Contributing
============

We welcome GitHub issues and pull requests - please see CONTRIBUTING.md for details.

License
=======

hhvm-autoload is [BSD-licensed](LICENSE). We also provide an additional [patent grant](PATENTS).
