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

final class PSR4Filter extends BasePSRFilter {
  protected static function getExpectedPathWithoutExtension(
    string $class_name,
    string $prefix,
    string $root,
  ): string {
    $local_part = \str_ireplace($prefix, '', $class_name);
    $expected = $root.\strtr($local_part, "\\", '/');
    return $expected;
  }
}
