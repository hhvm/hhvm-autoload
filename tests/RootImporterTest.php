<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\AutoloadMap;

final class RootImporterTest extends BaseTestCase {
  public function testSelf(): void {
    $root = realpath(__DIR__.'/../');
    $importer = new RootImporter($root, IncludedRoots::PROD_ONLY);
    $map = $importer->getAutoloadMap();
    $this->assertContains(
      'facebook\autoloadmap\exception',
      array_keys($map['class']),
    );

    $this->assertContains(
      'phpunit_framework_testcase',
      array_keys($map['class']),
    );
    $this->assertEmpty($importer->getFiles());
  }

  public function provideTestModes(): array<(IncludedRoots, string)> {
    return [
      tuple(IncludedRoots::PROD_ONLY, 'test-prod.php'),
      tuple(IncludedRoots::DEV_AND_PROD, 'test-dev.php'),
    ];
  }

  /** @dataProvider provideTestModes */
  public function testImportTree(
    IncludedRoots $included_roots,
    string $test_file,
  ): void {
    $root = __DIR__.'/fixtures/hh-only';
    $builder = new RootImporter($root, $included_roots);
    $tempfile = tempnam(sys_get_temp_dir(), 'hh_autoload');
    (new Writer())
      ->setBuilder($builder)
      ->setRoot($root)
      ->writeToFile($tempfile);

    $cmd = (Vector {
      PHP_BINARY,
      '-v', 'Eval.Jit=0',
      __DIR__.'/fixtures/hh-only/'.$test_file,
      $tempfile,
    })->map($x ==> escapeshellarg($x));
    $cmd = implode(' ', $cmd);

    $output = [];
    $exit_code = null;
    $result = exec($cmd, $output, $exit_code);

    unlink($tempfile);

    $this->assertSame(0, $exit_code, implode("\n", $output));
    $this->assertSame($result, 'OK!');
  }
}
