/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\AutoloadMap;

/**
 * A filter exposing any definitions from another builder that are compliant
 * with PSR-0.
 */
final class PSR0Filter extends BasePSRFilter {
  <<__Override>>
  protected static function getExpectedPathWithoutExtension(
    string $class_name,
    string $prefix,
    string $root,
  ): string {
    $class_name = \strtr($class_name, '\\', '/');

    // Underscores in namespace parts must be ignored, but those in the class
    // name need to be converted.
    $namespace = '';
    if (($last_namespace_sep = \strrpos($class_name, '/')) !== false) {
      $namespace = \substr($class_name, 0, $last_namespace_sep + 1);
      $class_name = \substr($class_name, $last_namespace_sep + 1);
    }

    return $root.$namespace.\strtr($class_name, '_', '/');
  }
}
