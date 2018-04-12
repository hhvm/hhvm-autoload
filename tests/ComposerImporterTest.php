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
    $this->assertEmpty($importer->getFiles());

    $map = $importer->getAutoloadMap();
    $this->assertSame(
      $root.'/src/Exception.php',
      idx($map['class'], 'facebook\autoloadmap\exception'),
    );
    $this->assertSame(
      $root.'/src/Writer.php',
      idx($map['class'], 'facebook\autoloadmap\writer'),
    );
    $this->assertSame(
      $root.'/src/Config.php',
      idx($map['type'], 'facebook\autoloadmap\config'),
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
    $this->assertEmpty($map['type']);
    $this->assertContains(
      $root.'/src/AutoloadMap.php',
      $importer->getFiles(),
    );
  }

  /**
   * @dataProvider getParsers
   */
  public function testPSR4Import(Parser $parser): void {
    $root = \realpath(__DIR__.'/fixtures/psr-4');
    $composer = $root.'/composer.json';
    $this->assertTrue(\file_exists($composer));

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

    $this->assertSame(
      $root.'/src-with-slash/PSR4Test.php',
      idx(
        $importer->getAutoloadMap()['class'],
        'psr4\testwithslash\psr4test',
      ),
    );

    $this->assertSame(
      $root.'/src/HHPSR4Test.hh',
      idx(
        $importer->getAutoloadMap()['class'],
        'psr4\test\hhpsr4test',
      ),
    );
  }

  /**
   * @dataProvider getParsers
   */
  public function testPSR4ImportNoTrailingSlash(Parser $parser): void {
    $root = \realpath(__DIR__.'/fixtures/psr-4');
    $composer = $root.'/composer.json';
    $this->assertTrue(\file_exists($composer));

    $composer_config = \json_decode(
      \file_get_contents($composer),
      /* as array = */ true,
    );
    $this->assertNotEmpty(
      $composer_config['autoload']['psr-4'],
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

    $this->assertSame(
      $root.'/src/PSR4Test.php',
      idx(
        $importer->getAutoloadMap()['class'],
        'psr4\test\psr4test',
      ),
    );
  }

  /**
   * @dataProvider getParsers
   */
  public function testPSR0Import(Parser $parser): void {
    $root = \realpath(__DIR__.'/fixtures/psr-0');
    $composer = $root.'/composer.json';
    $this->assertTrue(\file_exists($composer));

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
    $this->assertSame(
      $root.'/src-with-slash/PSR0TestWithSlash.php',
      idx(
        $importer->getAutoloadMap()['class'],
        'psr0testwithslash',
      ),
    );
  }

  /**
   * @dataProvider getParsers
   */
  public function testPSR0ImportNoTrailingSlash(Parser $parser): void {
    $root = \realpath(__DIR__.'/fixtures/psr-0');
    $composer = $root.'/composer.json';
    $this->assertTrue(\file_exists($composer));

    $composer_config = \json_decode(
      \file_get_contents($composer),
      /* as array = */ true,
    );
    $this->assertNotEmpty(
      $composer_config['autoload']['psr-0'],
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

    $this->assertSame(
      $root.'/src/PSR0Test.php',
      idx(
        $importer->getAutoloadMap()['class'],
        'psr0test',
      ),
    );

    $this->assertSame(
      $root.'/src/PSR0TestInHH.hh',
      idx(
        $importer->getAutoloadMap()['class'],
        'psr0testinhh',
      ),
    );
  }

  /**
   * @dataProvider getParsers
   */
  public function testPSR0ImportUnderscores(Parser $parser): void {
    $root = \realpath(__DIR__.'/fixtures/psr-0');
    $composer = $root.'/composer.json';
    $this->assertTrue(\file_exists($composer));

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

    $this->assertSame(
      $root.'/src-with-underscores/PSR0_Test_With_Underscores/Foo/Bar.php',
      idx(
        $importer->getAutoloadMap()['class'],
        'psr0_test_with_underscores\\foo_bar',
      ),
    );
  }
}
