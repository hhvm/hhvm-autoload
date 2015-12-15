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
        'roots' => ImmVector { $root },
      ),
    );
    $map = $importer->getAutoloadMap();
    $this->assertContains(
      'Facebook\AutoloadMap\Exception',
      array_keys($map['class']),
    );
    $this->assertContains(
      'PHPUnit_Framework_TestCase',
      array_keys($map['class']),
    );
    $this->assertEmpty($importer->getFiles());
  }
}
