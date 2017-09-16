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

<<__ConsistentConstruct>>
abstract class FailureHandler {
  /**
   * Called exactly once, once the autoload map has been set.
   */
  public function initialize(): void {
  }

  /** If the handler should be used.
   * If you have a fallback method (e.g. HHClientFallbackHandler), you might
   * want to return false if running in CI.
   */
  public function isEnabled(): bool {
    return true;
  }

  /** Any class, typedef, etc */
  abstract public function handleFailedType(string $name): void;

  abstract public function handleFailedFunction(string $name): void;

  abstract public function handleFailedConstant(string $name): void;

  final public function handleFailure(string $kind, string $name): void {
    if ($kind === 'class') {
     $this->handleFailedType($name);
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
     $this->handleFailedFunction($name);
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
     $this->handleFailedConstant($name);
      return;
    }
  }
}
