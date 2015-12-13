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

final class ComposerImporterTest extends \PHPUnit_Framework_TestCase {
  public function testRootImportWithScannedFiles(): void {
    $root = realpath(__DIR__.'/../');
    $importer = new ComposerImporter(
      $root.'/composer.json',
      shape(
        'autoloadFilesBehavior' => AutoloadFilesBehavior::FIND_DEFINITIONS,
        'composerJsonFallback' => true,
        'includeVendor' => false,
        'roots' => ImmVector { $root },
      ),
    );
    $this->assertEmpty($importer->getFiles());

    $map = $importer->getAutoloadMap();
    $this->assertSame(
      $root.'/src/Exception.php',
      idx($map['class'], 'Facebook\AutoloadMap\Exception'),
    );
    $this->assertSame(
      $root.'/src/Config.php',
      idx($map['type'], 'Facebook\AutoloadMap\Config'),
    );
  }

  public function testRootImportWithRequiredFiles(): void {
    $root = realpath(__DIR__.'/../');
    $importer = new ComposerImporter(
      $root.'/composer.json',
      shape(
        'autoloadFilesBehavior' => AutoloadFilesBehavior::EXEC_FILES,
        'composerJsonFallback' => true,
        'includeVendor' => false,
        'roots' => ImmVector { $root },
      ),
    );

    $map = $importer->getAutoloadMap();
    $this->assertEmpty($map['type']);
    $this->assertContains(
      $root.'/src/AutoloadMap.php',
      $importer->getFiles(),
    );
  }
}
