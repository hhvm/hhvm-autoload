HHVM-Autoload ![build status](https://api.travis-ci.org/fredemmott/hhvm-autoload.svg)
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

Preview Warning
===============

The autoload mechanism itself is very heavily tested at Facebook, however
[the library](https://github.com/fredemmott/definition-finder/) used to find the autoloadables (classes,
functions, etc) is fairly new, and has not been used on a wide variety of projects. It's been tested on:

 - The PHP and Hack code inside HHVM and Hack
 - [The Hack/HHVM documentation site](https://github.com/hhvm/user-documentation/)
 - [The dependencies](https://github.com/hhvm/user-documentation/blob/master/composer.lock) of the Hack/HHVM documentation site

If you encounter a parse error, please [file an issue](https://github.com/fredemmott/definition-finder/issues) against [fredemmott/definition-finder](https://github.com/fredemmott/definition-finder/) with either example code, or a link to an open source project that it can't parse.

For any other issue, please [file an issue](https://github.com/fredemmott/hhvm-autoload/issues) against [this project](https://github.com/fredemmott/hhvm-autoload).

Usage
=====

1. Add an `hh_autoload.json` file (see section below) and optionally remove your configuration from composer.json
2. `composer require fredemmott/hhvm-autoload`
3. Replace any references to `vendor/autoload.php` with  `vendor/hh_autoload.php`
4. If you are using PHPUnit, you will need to add `vendor/hh_autoload.php` to your `bootstrap.php`, or to `phpunit.xml` as a `bootstrap` file if you don't already have one. This is because PHPUnit automatically loads `vendor/autoload.php`, but is not aware of `vendor/hh_autoload.php`
5. To re-generate the map, run `composer dump-autoload` or any other command that generates the map

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
How It Works
============

 - [`fredemmott/definition-finder`](https://github.com/fredemmott/definition-finder/) provides a list of all PHP and Hack definitions in the specified locations
 - This is used to generate something similar to a classmap, except including other kinds of definitions
 - The map is provided to HHVM with [`HH\autoload_set_paths()`](https://docs.hhvm.com/hack/reference/function/HH.autoload_set_paths/)

The [Composer plugin API](https://getcomposer.org/doc/articles/plugins.md) allows it to re-generate the `vendor/hh_autoload.php` file automatically whenever Composer itself regenerates `vendor/autoload.php`

License
=======

hhvm-autoload is [BSD-licensed](LICENSE). We also provide an additional [patent grant](PATENTS).
