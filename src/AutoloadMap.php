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

/** The main shape of an autoload map.
 *
 * Must match `\HH\autoload_set_paths()`
 */
type AutoloadMap = shape(
  'class' => array<string, string>,
  'function' => array<string, string>,
  'type' => array<string, string>,
  'constant' => array<string, string>,
);
