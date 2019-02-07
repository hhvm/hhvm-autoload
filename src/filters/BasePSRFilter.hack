/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\AutoloadMap;

/** Base class for only removing non-PSR-compliant definitions from another
 * builder.
 *
 * @see `PSR0Filter` and `PSR4Filter`
 */
abstract class BasePSRFilter implements Builder {

  /** Return the path of the file that is expected to contain the specified
   * a class.
   *
   * @param $classname the fully-qualified classname
   * @param $prefix the prefix/namespace for this PSR configuration
   * @param $root the root directory for classes with the specified prefix
   */
  abstract protected static function getExpectedPathWithoutExtension(
    string $classname,
    string $prefix,
    string $root,
  ): string;

  /** Create a new `BasePSRFilter`
   *
   * @param $prefix the prefix/namespace for this PSR configuration
   * @param $root the root directory for classes with the specified prefix
   * @param $source a `Builder` containing definitions that will be filtered
   */
  final public function __construct(
    private string $prefix,
    private string $root,
    private Builder $source,
  ) {
    $this->root = \rtrim($this->root, '/').'/';
  }

  public function getFiles(): ImmVector<string> {
    return ImmVector {};
  }

  public function getAutoloadMap(): AutoloadMap {
    $classes =
      (new Map($this->source->getAutoloadMap()['class']))->filterWithKey(
        function(string $class_name, string $file): bool {
          if ($this->prefix !== '' && \stripos($class_name, $this->prefix) !== 0) {
            return false;
          }
          $expected = static::getExpectedPathWithoutExtension(
            $class_name,
            $this->prefix,
            $this->root,
          );
          $expected = \strtolower($expected);
          $file = \strtolower($file);
          return ($file === $expected.'.hh' || $file === $expected.'.php');
        },
      );

    return shape(
      'class' => $classes->toArray(),
      'function' => [],
      'type' => [],
      'constant' => [],
    );
  }
}
