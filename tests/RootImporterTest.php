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

final class RootImporterTest extends \PHPUnit_Framework_TestCase {
  public function testFullImport(): void {
    $root = realpath(__DIR__.'/../');
    $importer = new RootImporter(
      $root,
      shape(
        'autoloadFilesBehavior' => AutoloadFilesBehavior::FIND_DEFINITIONS,
        'includeVendor' => true,
        'roots' => ImmVector { $root.'/src' },
      ),
    );
    $map = $importer->getAutoloadMap();
    $this->assertContains(
      'fredemmott\autoloadmap\exception',
      array_keys($map['class']),
    );

    $this->assertContains(
      'phpunit_framework_testcase',
      array_keys($map['class']),
    );
    $this->assertEmpty($importer->getFiles());
  }

  public function testImportWithoutVendor(): void {
    $root = realpath(__DIR__.'/../');
    $importer = new RootImporter(
      $root,
      shape(
        'autoloadFilesBehavior' => AutoloadFilesBehavior::FIND_DEFINITIONS,
        'includeVendor' => false,
        'roots' => ImmVector { $root.'/src' },
      ),
    );

    $map = $importer->getAutoloadMap();
    $this->assertContains(
      'fredemmott\autoloadmap\exception',
      array_keys($map['class']),
    );
    $this->assertNotContains(
      'phpunit_framework_testcase',
      array_keys($map['class']),
    );
    $this->assertEmpty($importer->getFiles());
  }

  public function testImportTree(): void {
    $root = __DIR__.'/fixtures/hh-only';
    $builder = RootImporter::forTree($root);
    $tempfile = tempnam(sys_get_temp_dir(), 'hh_autoload');
    (new Writer())
      ->setBuilder($builder)
      ->setRoot($root)
      ->writeToFile($tempfile);

    // the file is meant to be in the root directory of the source, but
    // that's not good for testing. Fix it up.
    $codegen = file_get_contents($tempfile);
    $this->assertContains('__DIR__', $codegen);
    $codegen = str_replace('__DIR__', var_export($root, true), $codegen);
    file_put_contents($tempfile, $codegen);

    $cmd = (Vector {
      PHP_BINARY,
      '-v', 'Eval.Jit=0',
      __DIR__.'/fixtures/hh-only/test.php',
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
