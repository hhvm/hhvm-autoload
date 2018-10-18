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

use type Facebook\HackTest\DataProvider;
use function Facebook\FBExpect\expect;

final class RootImporterTest extends BaseTest {
  public function testSelf(): void {
    $root = \realpath(__DIR__.'/../');
    $importer = new RootImporter($root, IncludedRoots::PROD_ONLY);
    $map = $importer->getAutoloadMap();
    expect(\array_keys($map['class']))->toContain(
      'facebook\autoloadmap\exception',
    );

    expect(\array_keys($map['class']))->toContain(
      'facebook\\hacktest\\hacktest',
    );
    expect($importer->getFiles())->toBeEmpty();
  }

  public function provideTestModes(): array<(IncludedRoots, string, bool)> {
    return [
      tuple(IncludedRoots::PROD_ONLY, 'test-prod.php', true),
      tuple(IncludedRoots::PROD_ONLY, 'test-prod.php', false),
      tuple(IncludedRoots::DEV_AND_PROD, 'test-dev.php', true),
      tuple(IncludedRoots::DEV_AND_PROD, 'test-dev.php', false),
    ];
  }

  <<DataProvider('provideTestModes')>>
  public function testImportTree(
    IncludedRoots $included_roots,
    string $test_file,
    bool $relative_root,
  ): void {
    $root = __DIR__.'/fixtures/hh-only';
    $builder = new RootImporter($root, $included_roots);
    $tempdir = $relative_root ? $root.'/vendor' : \sys_get_temp_dir();
    $tempfile = \tempnam($tempdir, 'hh_autoload');
    (new Writer())
      ->setBuilder($builder)
      ->setRoot($root)
      ->setRelativeAutoloadRoot($relative_root)
      ->setIsDev(true)
      ->writeToFile($tempfile);

    $cmd = (
      Vector {
        \PHP_BINARY,
        '-v',
        'Eval.Jit=0',
        __DIR__.'/fixtures/hh-only/'.$test_file,
        $tempfile,
      }
    )->map($x ==> \escapeshellarg($x));
    $cmd = \implode(' ', $cmd);

    $output = [];
    $exit_code = null;
    $result = \exec($cmd, &$output, &$exit_code);

    $contents = \file_get_contents($tempfile);
    \unlink($tempfile);

    expect($exit_code)->toBeSame(0, \implode("\n", $output));
    expect('OK!')->toBeSame($result);

    if ($relative_root) {
      expect($contents)->toContain("__DIR__.'/../extrafile.php'");
    } else {
      expect($contents)->toContain('\''.$root.'/extrafile.php\'');
    }
  }

  public function testSingleArgConstructor(): void {
    // If a project uses <= 1.3, their existing composer plugin will try to do
    // this while upgrading, and error out if it fails - so, we need to keep
    // suppporting it.
    $root = __DIR__.'/fixtures/hh-only';
    $builder = new RootImporter($root);
    expect($builder)->toNotBeNull();
  }
}
