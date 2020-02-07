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
  'roots' => ImmVector<string>,
  'devRoots' => ImmVector<string>,
  'includeVendor' => bool,
  'extraFiles' => ImmVector<string>,
  'parser' => Parser,
  'failureHandler' => ?string,
  'devFailureHandler' => ?string,
  'relativeAutoloadRoot' => bool,
);
