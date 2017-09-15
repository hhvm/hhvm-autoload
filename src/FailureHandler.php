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

abstract class FailureHandler {
  /** If you implement any caching, please include this in the cache keys so that
   * re-building the map invalidates the cache. */
  <<__Memoize>>
  protected static function getBuildID(): string {
    $constant = 'Facebook\\AutoloadMap\\Generated\\BUILD_ID';
    if (\defined($constant)) {
      return \constant($constant);
    }
    return 'UNDEFINED!'.\bin2hex(\random_bytes(32));
  }

  /** If the handler should be used.
   * If you have a fallback method (e.g. HHClientFallbackHandler), you might
   * want to return false if running in CI.
   */
  public static function isEnabled(): bool {
    return true;
  }

  /** Any class, typedef, etc */
  abstract public static function handleFailedType(string $name): void;

  abstract public static function handleFailedFunction(string $name): void;

  abstract public static function handleFailedConstant(string $name): void;

  final public static function handleFailure(string $kind, string $name): void {
    if ($kind === 'class') {
      static::handleFailedType($name);
      return;
    }
    if ($kind === 'function') {
      $idx = \strrpos($name, '\\');
      if ($idx !== false) {
        $suffix = \substr($name, $idx + 1);
        if (\function_exists($suffix, /* autoload = */ false)) {
          return;
        }
      }
      static::handleFailedFunction($name);
      return;
    }
    if ($kind === 'constant') {
      $idx = \strrpos($name, '\\');
      if ($idx !== false) {
        $suffix = \substr($name, $idx + 1);
        if (\defined($suffix, /* autoload = */ false)) {
          return;
        }
      }
      static::handleFailedConstant($name);
      return;
    }
  }
}
