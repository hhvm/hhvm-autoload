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
 * Base interface for everything that exposes an autoload map.
 */
interface Builder {
  /** Returns the actual autoload map created by this builder */
  public function getAutoloadMap(): AutoloadMap;
  /** Returns any additional files that should be explicitly required on
   * start */
  public function getFiles(): vec<string>;
}
