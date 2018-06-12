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

use function Facebook\FBExpect\expect;

final class FallbackHandlerTest extends \PHPUnit_Framework_TestCase {
  public function setup(): void {
    if (!\function_exists('Facebook\\AutoloadMap\\Generated\\build_id')) {
      $this->markTestSkipped("Does not work with composer's autoloader");
    }
    if (!HHClientFallbackHandler::isEnabled()) {
      $this->markTestSkipped("Fallback handler is not enabled");
    }
  }
  
  public function testFunction(): void {
    expect(TestData\MixedCaseFunction())->toBeSame(
      \realpath(__DIR__.'/../testdata/MixedCaseFunction.php'),
    );
  }

  public function testConstant(): void {
    expect(TestData\MixedCaseConstant)->toBeSame(
      \realpath(__DIR__.'/../testdata/MixedCaseConstant.php'),
    );
  }
  public function testClass(): void {
    expect(TestData\MixedCaseClass::getContainingFile())->toBeSame(
      \realpath(__DIR__.'/../testdata/MixedCaseClass.php'),
    );
  }

  public function testTypedef(): void {
    expect(
      () ==> {
        $f = (TestData\MixedCaseType $in) ==> {};
        $f(new TestData\MixedCaseClass());
      },
    )->notToThrow();
  }
}
