/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\AutoloadMap;

use Facebook\AutoloadMap\_Private\TypeAssert;
use namespace HH\Lib\{C, Vec};

/** Create an autoload map from a directory using `ext_factparse`. */
final class FactParseScanner implements Builder {
  const type TFacts = darray<string, shape(
    'types' => varray<shape(
      'name' => string,
      'kindOf' => string,
    )>,
    'constants' => varray<string>,
    'functions' => varray<string>,
    'typeAliases' => varray<string>,
  )>;

  private static function untypedToShape(mixed $data): self::TFacts {
    invariant(
      $data is KeyedTraversable<_, _>,
      'FactsParse did not give us an array',
    );

    $out = darray[];
    foreach ($data as $file => $facts) {
      invariant($file is string, 'FactsParse data is not string-keyed');
      invariant(
        $facts is KeyedContainer<_, _>,
        'FactsParse data for file "%s" is not a KeyedContainer',
        $file,
      );

      try {
        $types = TypeAssert\is_array_of_shapes_with_name_field_and_kind(
          $facts['types'] ?? vec[],
          'FactParse types',
        );
        $out[$file] = shape(
          'types' => $types,
          'constants' => TypeAssert\is_array_of_strings(
            $facts['constants'] ?? vec[],
            'FactParse constants',
          ),
          'functions' => TypeAssert\is_array_of_strings(
            $facts['functions'] ?? vec[],
            'FactParse functions',
          ),
          'typeAliases' => TypeAssert\is_array_of_strings(
            $facts['typeAliases'] ?? vec[],
            'FactParse typeAliases',
          ),
        );

        // On hhvm >4.160, typeAliases may not be present,
        // we can extract type aliases from `types` where `kindOf` === `typeAlias`.
        if (!C\contains_key($facts, 'typeAliases')) {
          $out[$file]['typeAliases'] =
            Vec\filter($types, $shape ==> $shape['kindOf'] === 'typeAlias')
            |> Vec\map($$, $shape ==> $shape['name']);
        }
      } catch (\Exception $e) {
        $error_level = \error_reporting(0);
        $file_is_empty = \filesize($file) === 0;
        \error_reporting($error_level);
        if ($file_is_empty) {
          continue;
        }
        throw new \Exception("Failed to parse '".$file.'"', $e->getCode(), $e);
      }
    }
    return $out;
  }

  private function __construct(
    private string $root,
    private vec<string> $paths,
  ) {
    $version = (int)\phpversion('factparse');
    invariant(
      $version === 3,
      'Factparse version 3 is required, got %d',
      $version,
    );
  }

  public static function fromFile(string $path): Builder {
    return new FactParseScanner('', vec[$path]);
  }

  public static function fromTree(string $root): Builder {
    $paths = vec[];
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
      if (
        $ext !== 'php' &&
        $ext !== 'hh' &&
        $ext !== 'xhp' &&
        $ext !== 'hack' &&
        $ext !== 'hck'
      ) {
        continue;
      }
      $paths[] = $info->getPathname();
    }

    return new FactParseScanner($root, $paths);
  }

  public function getAutoloadMap(): AutoloadMap {
    $facts = \HH\facts_parse(
      $this->root,
      varray($this->paths),
      /* force_hh = */ false,
      /* multithreaded = */ true,
    );
    $facts = self::untypedToShape($facts);

    $classes = dict[];
    $functions = dict[];
    $types = dict[];
    $constants = dict[];
    foreach ($facts as $file => $file_facts) {
      foreach ($file_facts['types'] as $type) {
        $classes[\strtolower($type['name'])] = $file;
      }
      foreach ($file_facts['constants'] as $const) {
        $constants[$const] = $file;
      }
      foreach ($file_facts['functions'] as $func) {
        $functions[\strtolower($func)] = $file;
      }
      foreach ($file_facts['typeAliases'] as $alias) {
        $types[\strtolower($alias)] = $file;
      }
    }
    return dict[
      'class' => $classes,
      'function' => $functions,
      'type' => $types,
      'constant' => $constants,
    ];
  }

  public function getFiles(): vec<string> {
    return vec[];
  }
}
