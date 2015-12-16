<?hh // strict
/*
 *  Copyright (c) 2015, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\AutoloadMap;

final class BootstrapTest extends \PHPUnit_Framework_TestCase {
  public function testBootstrap(): void {

    $cmd = (Vector {
      PHP_BINARY,
      '-v', 'Eval.Jit=0',
      __DIR__.'/fixtures/bootstrap_test.php',
      __DIR__.'/../bootstrap.php',
    })->map($x ==> escapeshellarg($x));
    $cmd = implode(' ', $cmd);

    $output = [];
    $exit_code = null;
    $result = exec($cmd, $output, $exit_code);
    $output = implode("\n", $output);

    $this->assertSame(0, $exit_code, $output);
    $this->assertSame($result, 'scan', $output);

  }
}
