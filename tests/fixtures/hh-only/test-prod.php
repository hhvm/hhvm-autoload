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

<<__EntryPoint>>
function test_prod_main(): void {
  $argv_1 = \HH\global_get('argv') as KeyedContainer<_, _>[1] as string;
  require($argv_1);
  \var_dump($argv_1);
  \var_dump(\file_get_contents($argv_1));
  \Facebook\AutoloadMap\initialize();

  $x = new ExampleClass();
  example_function();
  $x = \FREDEMMOTT_AUTOLOAD_MAP_TEST_FIXTURES_EXAMPLE_CONSTANT;
  $x = (ExampleType $x) ==> null;
  $x = (ExampleNewtype $x) ==> null;
  $x = ExampleEnum::HERP;

  invariant(
    \class_exists(VendorHHExampleClass::class),
    "Should be able to load class from vendor hh_autoload.json",
  );
  invariant(
    !\class_exists(VendorComposerExampleClass::class),
    "Should *not* be able to load class from vendor composer.json if there's also ".
    "an hh_autoload.json",
  );
  invariant(
    !\class_exists(MyExampleTest::class),
    "Should *not* be able to load class from dev root in prod mode",
  );
  print("OK!");
}
