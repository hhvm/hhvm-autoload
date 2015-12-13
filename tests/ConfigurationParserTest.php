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

final class ConfigurationLoaderTest extends \PHPUnit_Framework_TestCase {
  public function goodTestCases(
  ): array<string, array<array<string, mixed>>> {
    return [
      'fully specified' => [[
        'autoloadFilesBehavior' => AutoloadFilesBehavior::EXEC_FILES,
        'composerJsonFallback' => true,
        'roots' => ['foo/', 'bar/'],
      ]],
      'just roots' => [[
        'roots' => ['foo/', 'bar/'],
      ]],
    ];
  }

  /**
   * @dataProvider goodTestCases
   */
  public function testGoodConfig(array<string, mixed> $data): void {
    $config = ConfigurationLoader::fromData($data, '/dev/null');

    $this->assertEquals(
      $data['roots'],
      $config['roots']->toArray(),
    );

    $this->assertNotNull(
      AutoloadFilesBehavior::coerce($config['autoloadFilesBehavior'])
    );
    $this->assertTrue(is_bool($config['composerJsonFallback']));

    $config = Shapes::toArray($config); 
    foreach ($data as $key => $value) {
      if ($key === 'roots') {
        continue;
      }
      $this->assertSame($value, $config[$key]);
    }
  }
}
