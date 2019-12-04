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
 * However, this is nolonger accurate.
 * The parameter of autoload_set_paths is a
 * `KeyedContainer<string, KeyedContainer<string, string>>`.
 * This does sadly not allow for the fallback key,
 * which is a (function(string, string): bool).
 */
type AutoloadMap = shape(
  'class' => darray<string, string>,
  'function' => darray<string, string>,
  'type' => darray<string, string>,
  'constant' => darray<string, string>,
);
