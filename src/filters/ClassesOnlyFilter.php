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

final class ClassesOnlyFilter implements Builder {
  public function __construct(
    private Builder $source,
  ) {
  }

  public function getFiles(): ImmVector<string> {
    return ImmVector { };
  }

  public function getAutoloadMap(): AutoloadMap {
    return shape(
      'class' => $this->source->getAutoloadMap()['class'],
      'function' => [],
      'type' => [],
      'constant' => [],
    );
  }
}
