/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\AutoloadMap;

/** Handle autoload requests for definitions that aren't in the map.
 *
 * If the handlers load a definition, then no error will be raised and the
 * autoload will be considered successful.
 */
<<__ConsistentConstruct>>
abstract class FailureHandler {
  // Required for coeffects/capabilities to be defaults rather than pure
  public function __construct() {
  }

  /**
   * Called exactly once, once the autoload map has been set.
   */
  public function initialize(): void {}

  /** If the handler should be used.
   * If you have a fallback method (e.g. HHClientFallbackHandler), you might
   * want to return false if running in CI.
   */
  public static function isEnabled(): bool {
    return true;
  }

  /** Handle a class, typedef, enum etc */
  abstract public function handleFailedType(string $name): void;

  /** Handle a function (not methods) */
  abstract public function handleFailedFunction(string $name): void;

  /** Handle a constant lookup */
  abstract public function handleFailedConstant(string $name): void;

  /** Main entry point.
   *
   * Parameters exactly match the expected parameters for a fallback function
   * in `HH\autoload_set_paths()`.
   */
  final public function handleFailure(string $kind, string $name): void {
    if ($kind === 'class' || $kind === 'type') {
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
