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

final class ComposerImporterTest extends BaseTestCase {
  /**
   * @dataProvider getParsers
   */
  public function testRootImportWithScannedFiles(Parser $parser): void {
    $root = \realpath(__DIR__.'/../');
    $importer = new ComposerImporter(
      $root.'/composer.json',
      shape(
        'autoloadFilesBehavior' => AutoloadFilesBehavior::FIND_DEFINITIONS,
        'includeVendor' => false,
        'extraFiles' => ImmVector { },
        'roots' => ImmVector { $root },
        'devRoots' => ImmVector { },
        'parser' => $parser,
        'relativeAutoloadRoot' => true,
        'failureHandler' => null,
        'devFailureHandler' => null,
      ),
    );
    expect($importer->getFiles())->toBeEmpty();

    $map = $importer->getAutoloadMap();
    expect(idx($map['class'], 'facebook\autoloadmap\exception'))->toBeSame(
      $root.'/src/Exception.php',
    );
    expect(idx($map['class'], 'facebook\autoloadmap\writer'))->toBeSame(
      $root.'/src/Writer.php',
    );
    expect(idx($map['type'], 'facebook\autoloadmap\config'))->toBeSame(
      $root.'/src/Config.php',
    );
  }

  /**
   * @dataProvider getParsers
   */
  public function testRootImportWithRequiredFiles(Parser $parser): void {
    $root = \realpath(__DIR__.'/../');
    $importer = new ComposerImporter(
      $root.'/composer.json',
      shape(
        'autoloadFilesBehavior' => AutoloadFilesBehavior::EXEC_FILES,
        'includeVendor' => false,
        'extraFiles' => ImmVector { },
        'roots' => ImmVector { $root },
        'devRoots' => ImmVector { },
        'parser' => $parser,
        'relativeAutoloadRoot' => true,
        'failureHandler' => null,
        'devFailureHandler' => null,
      ),
    );

    $map = $importer->getAutoloadMap();
    expect($map['type'])->toBeEmpty();
    expect($importer->getFiles())->toContain($root.'/src/AutoloadMap.php');
  }

  /**
   * @dataProvider getParsers
   */
  public function testPSR4Import(Parser $parser): void {
    $root = \realpath(__DIR__.'/fixtures/psr-4');
    $composer = $root.'/composer.json';
    expect(\file_exists($composer))->toBeTrue();

    $composer_config = \json_decode(
      \file_get_contents($composer),
      /* as array = */ true,
    );

    $importer = new ComposerImporter(
      $composer,
      shape(
        'autoloadFilesBehavior' => AutoloadFilesBehavior::EXEC_FILES,
        'includeVendor' => false,
        'extraFiles' => ImmVector { },
        'roots' => ImmVector { $root },
        'devRoots' => ImmVector { },
        'parser' => $parser,
        'relativeAutoloadRoot' => true,
        'failureHandler' => null,
        'devFailureHandler' => null,
      ),
    );

    expect(
      idx($importer->getAutoloadMap()['class'], 'psr4\testwithslash\psr4test'),
    )->toBeSame($root.'/src-with-slash/PSR4Test.php');

    expect(idx($importer->getAutoloadMap()['class'], 'psr4\test\hhpsr4test'))
      ->toBeSame($root.'/src/HHPSR4Test.hh');
  }

  /**
   * @dataProvider getParsers
   */
  public function testPSR4ImportNoTrailingSlash(Parser $parser): void {
    $root = \realpath(__DIR__.'/fixtures/psr-4');
    $composer = $root.'/composer.json';
    expect(\file_exists($composer))->toBeTrue();

    $composer_config = \json_decode(
      \file_get_contents($composer),
      /* as array = */ true,
    );
    expect($composer_config['autoload']['psr-4'])->toNotBeEmpty();

    $importer = new ComposerImporter(
      $composer,
      shape(
        'autoloadFilesBehavior' => AutoloadFilesBehavior::EXEC_FILES,
        'includeVendor' => false,
        'extraFiles' => ImmVector { },
        'roots' => ImmVector { $root },
        'devRoots' => ImmVector { },
        'parser' => $parser,
        'relativeAutoloadRoot' => true,
        'failureHandler' => null,
        'devFailureHandler' => null,
      ),
    );

    expect(idx($importer->getAutoloadMap()['class'], 'psr4\test\psr4test'))
      ->toBeSame($root.'/src/PSR4Test.php');
  }

  /**
   * @dataProvider getParsers
   */
  public function testPSR4ImportWithoutPrefix(Parser $parser): void {
    $root = \realpath(__DIR__.'/fixtures/psr-4');
    $composer = $root.'/composer.json';
    expect(\file_exists($composer))->toBeTrue();

    $composer_config = \json_decode(
      \file_get_contents($composer),
      /* as array = */ true,
    );
    expect($composer_config['autoload']['psr-4'])->toNotBeEmpty();

    $importer = new ComposerImporter(
      $composer,
      shape(
        'autoloadFilesBehavior' => AutoloadFilesBehavior::EXEC_FILES,
        'includeVendor' => false,
        'extraFiles' => ImmVector { },
        'roots' => ImmVector { $root },
        'devRoots' => ImmVector { },
        'parser' => $parser,
        'relativeAutoloadRoot' => true,
        'failureHandler' => null,
        'devFailureHandler' => null,
      ),
    );

    expect(
      idx(
        $importer->getAutoloadMap()['class'],
        'psr4\testwithoutprefix\psr4test',
      ),
    )->toBeSame(
      $root.'/src-without-prefix/PSR4/TestWithoutPrefix/PSR4Test.php',
    );
  }

  /**
   * @dataProvider getParsers
   */
  public function testPSR0Import(Parser $parser): void {
    $root = \realpath(__DIR__.'/fixtures/psr-0');
    $composer = $root.'/composer.json';
    expect(\file_exists($composer))->toBeTrue();

    $composer_config= \json_decode(
      \file_get_contents($composer),
      /* as array = */ true,
    );

    $importer = new ComposerImporter(
      $composer,
      shape(
        'autoloadFilesBehavior' => AutoloadFilesBehavior::EXEC_FILES,
        'includeVendor' => false,
        'extraFiles' => ImmVector { },
        'roots' => ImmVector { $root },
        'devRoots' => ImmVector { },
        'parser' => $parser,
        'relativeAutoloadRoot' => true,
        'failureHandler' => null,
        'devFailureHandler' => null,
      ),
    );
    expect(idx($importer->getAutoloadMap()['class'], 'psr0testwithslash'))
      ->toBeSame($root.'/src-with-slash/PSR0TestWithSlash.php');
  }

  /**
   * @dataProvider getParsers
   */
  public function testPSR0ImportNoTrailingSlash(Parser $parser): void {
    $root = \realpath(__DIR__.'/fixtures/psr-0');
    $composer = $root.'/composer.json';
    expect(\file_exists($composer))->toBeTrue();

    $composer_config = \json_decode(
      \file_get_contents($composer),
      /* as array = */ true,
    );
    expect($composer_config['autoload']['psr-0'])->toNotBeEmpty();

    $importer = new ComposerImporter(
      $composer,
      shape(
        'autoloadFilesBehavior' => AutoloadFilesBehavior::EXEC_FILES,
        'includeVendor' => false,
        'extraFiles' => ImmVector { },
        'roots' => ImmVector { $root },
        'devRoots' => ImmVector { },
        'parser' => $parser,
        'relativeAutoloadRoot' => true,
        'failureHandler' => null,
        'devFailureHandler' => null,
      ),
    );

    expect(idx($importer->getAutoloadMap()['class'], 'psr0test'))->toBeSame(
      $root.'/src/PSR0Test.php',
    );

    expect(idx($importer->getAutoloadMap()['class'], 'psr0testinhh'))->toBeSame(
      $root.'/src/PSR0TestInHH.hh',
    );
  }

  /**
   * @dataProvider getParsers
   */
  public function testPSR0ImportUnderscores(Parser $parser): void {
    $root = \realpath(__DIR__.'/fixtures/psr-0');
    $composer = $root.'/composer.json';
    expect(\file_exists($composer))->toBeTrue();

    $composer_config = \json_decode(
      \file_get_contents($composer),
      /* as array = */ true,
    );

    $importer = new ComposerImporter(
      $composer,
      shape(
        'autoloadFilesBehavior' => AutoloadFilesBehavior::EXEC_FILES,
        'includeVendor' => false,
        'extraFiles' => ImmVector {},
        'roots' => ImmVector { $root },
        'devRoots' => ImmVector {},
        'parser' => $parser,
        'relativeAutoloadRoot' => true,
        'failureHandler' => null,
        'devFailureHandler' => null,
      ),
    );

    expect(
      idx(
        $importer->getAutoloadMap()['class'],
        'psr0_test_with_underscores\\foo_bar',
      ),
    )->toBeSame(
      $root.'/src-with-underscores/PSR0_Test_With_Underscores/Foo/Bar.php',
    );
  }

  /**
   * @dataProvider getParsers
   */
  public function testPSR0ImportWithoutPrefix(Parser $parser): void {
    $root = \realpath(__DIR__.'/fixtures/psr-0');
    $composer = $root.'/composer.json';
    expect(\file_exists($composer))->toBeTrue();

    $composer_config = \json_decode(
      \file_get_contents($composer),
      /* as array = */ true,
    );
    expect($composer_config['autoload']['psr-0'])->toNotBeEmpty();

    $importer = new ComposerImporter(
      $composer,
      shape(
        'autoloadFilesBehavior' => AutoloadFilesBehavior::EXEC_FILES,
        'includeVendor' => false,
        'extraFiles' => ImmVector {},
        'roots' => ImmVector { $root },
        'devRoots' => ImmVector {},
        'parser' => $parser,
        'relativeAutoloadRoot' => true,
        'failureHandler' => null,
        'devFailureHandler' => null,
      ),
    );

    expect(
      idx(
        $importer->getAutoloadMap()['class'],
        'psr0testwithoutprefix',
      ),
    )->toBeSame(
      $root.'/src-without-prefix/PSR0TestWithoutPrefix.php',
    );
  }
}
