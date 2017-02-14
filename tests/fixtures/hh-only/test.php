<?hh
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
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

invariant(
  class_exists(VendorHHExampleClass::class),
  "Should be able to load class from vendor hh_autoload.json"
);
invariant(
  !class_exists(VendorComposerExampleClass::class),
  "Should *not* able to load class from vendor composer.json if there's also ".
  "an hh_autoload.json"
);
print("OK!");
