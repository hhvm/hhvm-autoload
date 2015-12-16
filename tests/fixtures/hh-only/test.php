<?hh
/*
 *  Copyright (c) 2015, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\AutoloadMap\TestFixtures;

require($argv[1]);

$x = new ExampleClass();
example_function();
$x = FREDEMMOTT_AUTOLOAD_MAP_TEST_FIXTURES_EXAMPLE_CONSTANT;
$x = (ExampleType $x) ==> null;
$x = (ExampleNewtype $x) ==> null;
$x = ExampleEnum::HERP;

print("OK!");
