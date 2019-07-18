<?hh // partial
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\AutoloadMap\TestFixtures;

/* HH_FIXME[1002] HHAST: move to <<__EntryPoint>> function */ require($argv[1]);

$x = \Facebook\AutoloadMap\AutoloadFilesBehavior::FIND_DEFINITIONS;

print($x);
