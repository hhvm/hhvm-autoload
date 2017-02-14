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

abstract final class Merger {
  public static function merge(
    \ConstVector<AutoloadMap> $maps,
  ): AutoloadMap {
    return shape(
      'class' =>
        self::mergeImpl($maps->map($map ==> $map['class'])),
      'function' =>
        self::mergeImpl($maps->map($map ==> $map['function'])),
      'type' =>
        self::mergeImpl($maps->map($map ==> $map['type'])),
      'constant' =>
        self::mergeImpl($maps->map($map ==> $map['constant'])),
    );
  }

  private static function mergeImpl(
    Iterable<array<string, string>> $maps,
  ): array<string, string> {
    $out = [];
    foreach ($maps as $map) {
      foreach ($map as $def => $file) {
        $out[$def] = $file;
      }
    }
    return $out;
  }
}
