<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\AutoloadMap;

use Facebook\TypeAssert\TypeAssert;

final class FactParseScanner implements Builder {
  const type TFacts = array<string, shape(
    'types' => array<shape(
      'name' => string,
    )>,
    'constants' => array<string>,
    'functions' => array<string>,
    'typeAliases' => array<string>,
  )>;

  private function __construct(
    private string $root,
    private ImmVector<string> $paths,
  ) {
    $version = (int) phpversion('factparse');
    invariant(
      $version === 3,
      'Factparse version 3 is required, got %d',
      $version,
    );
  }

  public static function fromFile(
    string $path,
  ): Builder {
    return new FactParseScanner('', ImmVector { $path });
  }

  public static function fromTree(
    string $root,
  ): Builder {
    $paths = Vector { };
    $rdi = new \RecursiveDirectoryIterator($root);
    $rii = new \RecursiveIteratorIterator($rdi);
    foreach ($rii as $info) {
      if (!$info->isFile()) {
        continue;
      }
      if (!$info->isReadable()) {
        continue;
      }
      $ext = $info->getExtension();
      if ($ext !== 'php' && $ext !== 'hh' && $ext !== 'xhp') {
        continue;
      }
      $paths[] = $info->getPathname();
    }

    return new FactParseScanner($root, $paths->immutable());
  }

  public function getAutoloadMap(): AutoloadMap {
    /* HH_FIXME[2049] no HHI for \HH\facts_parse */
    /* HH_FIXME[4107] no HHI for \HH|facts_parse */
    $facts = \HH\facts_parse(
      $this->root,
      $this->paths->toArray(),
      /* force_hh = */ false,
      /* multithreaded = */ true,
    );
    $facts = TypeAssert::matchesTypeStructure(
      type_structure(self::class, 'TFacts'),
      $facts,
    );

    $classes = [];
    $functions = [];
    $types = [];
    $constants = [];
    foreach ($facts as $file => $file_facts) {
      foreach ($file_facts['types'] as $type) {
        $classes[strtolower($type['name'])] = $file;
      }
      foreach ($file_facts['constants'] as $const) {
        $constants[$const] = $file;
      }
      foreach ($file_facts['functions'] as $func) {
        $functions[strtolower($func)] = $file;
      }
      foreach ($file_facts['typeAliases'] as $alias) {
        $types[strtolower($alias)] = $file;
      }
    }
    return shape(
      'class' => $classes,
      'function' => $functions,
      'type' => $types,
      'constant' => $constants,
    );
  }

  public function getFiles(): ImmVector<string> {
    return ImmVector { };
  }
}
