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

/** Class for merging multiple autoload maps.
 *
 * For example, we may merge:
 * - the root autoload map
 * - additional autoload maps for each vendored dependency
 */
abstract final class Merger {
  /** Return a new map containing all the entries from the input maps.
   *
   * In the case of duplicates, the last definition is used.
   */
  public static function merge(vec<AutoloadMap> $maps): AutoloadMap {
    return dict[
      'class' => self::mergeImpl(Vec\map($maps, $map ==> $map['class'])),
      'function' => self::mergeImpl(Vec\map($maps, $map ==> $map['function'])),
      'type' => self::mergeImpl(Vec\map($maps, $map ==> $map['type'])),
      'constant' => self::mergeImpl(Vec\map($maps, $map ==> $map['constant'])),
    ];
  }

  private static function mergeImpl(
    Traversable<KeyedTraversable<string, string>> $maps,
  ): dict<string, string> {
    $out = dict[];
    foreach ($maps as $map) {
      foreach ($map as $def => $file) {
        $out[$def] = $file;
      }
    }
    return $out;
  }
}
