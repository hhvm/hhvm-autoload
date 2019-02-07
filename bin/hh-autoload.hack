#!/usr/bin/env hhvm
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\AutoloadMap;

use namespace HH\Lib\Vec;

final class GenerateScript {
  const type TOptions = shape(
    'dev' => bool,
  );

  private static function initBootstrapAutoloader(): void {
    // NO HSL HERE - autoloader is not yet initialized
    $roots = vec[
      \realpath(__DIR__.'/..//src'),
      \getcwd().'/vendor/hhvm/hsl/src',
    ];
    $paths = varray[];
    foreach ($roots as $root) {
      foreach (self::getFileList($root) as $path) {
        $paths[] = $path;
      }
    }

    $facts = \HH\facts_parse(
      '/',
      $paths,
      /* force_hh = */ false,
      /* use_threads = */ true,
    );

    $map = darray[
      'class' => darray[],
      'function' => darray[],
      'type' => darray[],
      'constant' => darray[],
    ];

    foreach ($facts as $path => $file_facts) {
      if ($file_facts === null) {
        continue;
      }
      $file_facts = $file_facts as dynamic;
      foreach ($file_facts['types'] as $type) {
        $map['class'][\strtolower($type['name'] as string)] = $path;
      }
      foreach ($file_facts['constants'] as $const) {
        $map['constant'][$const as string] = $path;
      }
      foreach ($file_facts['functions'] as $fun) {
        $map['function'][\strtolower($fun as string)] = $path;
      }
      foreach ($file_facts['typeAliases'] as $type) {
        $map['type'][\strtolower($type as string)] = $path;
      }
    }
    \HH\autoload_set_paths($map, '/');
  }

  public static function main(vec<string> $argv): void {
    self::checkRoot();
    self::initBootstrapAutoloader();
    $options = self::parseOptions($argv);
    self::generateAutoloader($options);
  }

  private static function parseOptions(vec<string> $argv): self::TOptions {
    $options = shape(
      'dev' => true,
    );
    $bin = $argv[0];
    $argv = Vec\slice($argv, 1);
    foreach ($argv as $arg) {
      if ($arg === '--no-dev') {
        $options['dev'] = false;
        continue;
      }
      if ($arg === '--help') {
        self::printUsage(\STDOUT, $bin);
        exit(0);
      }
      \fprintf(\STDERR, "Unrecognized option: '%s'\n", $arg);
      self::printUsage(\STDERR, $bin);
      exit(1);
    }
    return $options;
  }

  private static function checkRoot(): void {
    // NO HSL HERE - autoloader is not yet initialized
    if (!\file_exists('hh_autoload.json')) {
      \fwrite(
        \STDERR,
        "This executable must be ran from a directory containing an ".
        "hh_autoload.json\n",
      );
      exit(1);
    }
  }

  private static function generateAutoloader(self::TOptions $options): void {
    $importer = new RootImporter(
      \getcwd(),
      $options['dev']
        ? IncludedRoots::DEV_AND_PROD
        : IncludedRoots::PROD_ONLY,
    );

    $handler = $options['dev']
      ? ($importer->getConfig()['devFailureHandler'] ?? null)
      : ($importer->getConfig()['failureHandler'] ?? null);

    (new Writer())
      ->setBuilder($importer)
      ->setRoot(\getcwd())
      ->setRelativeAutoloadRoot($importer->getConfig()['relativeAutoloadRoot'])
      ->setFailureHandler(/* HH_IGNORE_ERROR[4110] */ $handler)
      ->setIsDev($options['dev'])
      ->writeToDirectory(\getcwd().'/vendor/');
    print(\getcwd()."/vendor/autoload.hack\n");
  }

  private static function printUsage(
    resource $to,
    string $bin,
  ): void {
    \fprintf($to, "USAGE: %s [--no-dev]\n", $bin);
  }

  private static function getFileList(string $root): vec<string> {
    // NO HSL HERE - autoloader is not yet initialized
    $rdi = new \RecursiveDirectoryIterator($root);
    $rii = new \RecursiveIteratorIterator(
      $rdi,
      \RecursiveIteratorIterator::CHILD_FIRST,
    );
    $out = vec[];
    // All we care about is autoloading hhvm-autoload itself and the HSL;
    // we don't need to support every valid extension
    $extensions = keyset['php', 'hack'];
    foreach ($rii as $file_info) {
      if (!$file_info->isFile()) {
        continue;
      }
      if (($extensions[$file_info->getExtension()] ?? false) == false) {
        continue;
      }
      $out[] = $file_info->getPathname();
    }

    return $out;
  }
}

<<__EntryPoint>>
function cli_main(): noreturn {
  GenerateScript::main(vec(/* HH_IGNORE_ERROR[2050] */ $GLOBALS['argv']));
  exit(0);
}
