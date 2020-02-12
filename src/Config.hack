/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\AutoloadMap;

/** Shape of `hh_autoload.json` */
type Config = shape(
  'roots' => vec<string>,
  'devRoots' => vec<string>,
  'includeVendor' => bool,
  'extraFiles' => vec<string>,
  'parser' => Parser,
  'failureHandler' => ?string,
  'devFailureHandler' => ?string,
  'relativeAutoloadRoot' => bool,
);
