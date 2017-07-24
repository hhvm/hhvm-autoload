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

final class ComposerImporterTest extends BaseTestCase {
  /**
   * @dataProvider getParsers
   */
  public function testRootImportWithScannedFiles(Parser $parser): void {
    $root = realpath(__DIR__.'/../');
    $importer = new ComposerImporter(
      $root.'/composer.json',
      shape(
        'autoloadFilesBehavior' => AutoloadFilesBehavior::FIND_DEFINITIONS,
        'includeVendor' => false,
        'extraFiles' => ImmVector { },
        'roots' => ImmVector { $root },
        'devRoots' => ImmVector { },
        'parser' => $parser,
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
    $root = realpath(__DIR__.'/../');
    $importer = new ComposerImporter(
      $root.'/composer.json',
      shape(
        'autoloadFilesBehavior' => AutoloadFilesBehavior::EXEC_FILES,
        'includeVendor' => false,
        'extraFiles' => ImmVector { },
        'roots' => ImmVector { $root },
        'devRoots' => ImmVector { },
        'parser' => $parser,
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
    // This is brittle, but loud and easy to diagnoze + replace...
    $root = realpath(__DIR__.'/../vendor/symfony/yaml');
    $composer = $root.'/composer.json';
    $this->assertTrue(file_exists($composer));

    $composer_config = json_decode(
      file_get_contents($composer),
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
      ),
    );

    $this->assertSame(
      $root.'/Dumper.php',
      idx(
        $importer->getAutoloadMap()['class'],
        'symfony\component\yaml\dumper',
      ),
    );
  }

  /**
   * @dataProvider getParsers
   */
  public function testPSR4ImportNoTrailingSlash(Parser $parser): void {
    $root = realpath(__DIR__.'/fixtures/psr-4');
    $composer = $root.'/composer.json';
    $this->assertTrue(file_exists($composer));

    $composer_config = json_decode(
      file_get_contents($composer),
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
    // This is brittle, but loud and easy to diagnoze + replace...
    $root = realpath(__DIR__.'/../vendor/phpspec/prophecy');
    $composer = $root.'/composer.json';
    $this->assertTrue(file_exists($composer));

    $composer_config= json_decode(
      file_get_contents($composer),
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
      ),
    );
    $this->assertSame(
      $root.'/src/Prophecy/Prophet.php',
      idx(
        $importer->getAutoloadMap()['class'],
        'prophecy\prophet',
      ),
    );
  }

  /**
   * @dataProvider getParsers
   */
  public function testPSR0ImportNoTrailingSlash(Parser $parser): void {
    $root = realpath(__DIR__.'/fixtures/psr-0');
    $composer = $root.'/composer.json';
    $this->assertTrue(file_exists($composer));

    $composer_config = json_decode(
      file_get_contents($composer),
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
      ),
    );

    $this->assertSame(
      $root.'/src/PSR0Test.php',
      idx(
        $importer->getAutoloadMap()['class'],
        'psr0test',
      ),
    );
  }
}
