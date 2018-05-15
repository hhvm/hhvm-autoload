<?hh // strict
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
 * Remove any definitions from files that aren't in a specified prefix.
 */
final class PathExclusionFilter implements Builder {
  public function __construct(
    private Builder $source,
    private ImmSet<string> $prefixes,
  ) {
  }

  public function getFiles(): ImmVector<string> {
    return ImmVector { };
  }

  public function getAutoloadMap(): AutoloadMap {
    $map = $this->source->getAutoloadMap();
    return shape(
      'class' => $this->filter($map['class']),
      'function' => $this->filter($map['function']),
      'type' => $this->filter($map['type']),
      'constant' => $this->filter($map['constant']),
    );
  }

  private function filter(
    array<string, string> $map,
  ): array<string, string> {
    return \array_filter(
      $map,
      function(string $path): bool {
        foreach ($this->prefixes as $prefix) {
          if (\strpos($path, $prefix) === 0) {
            return false;
          }
        }
        return true;
      },
    );
  }
}
