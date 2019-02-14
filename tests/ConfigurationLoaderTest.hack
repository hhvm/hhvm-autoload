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

final class ConfigurationLoaderTest extends \Facebook\HackTest\HackTest {
  public function goodTestCases(
  ): array<string, array<array<string, mixed>>> {
    return [
      'fully specified' => [[
        'autoloadFilesBehavior' => AutoloadFilesBehavior::EXEC_FILES,
        'relativeAutoloadRoot' => false,
        'includeVendor' => false,
        'extraFiles' => [],
        'roots' => ['foo/', 'bar/'],
        'parser' => 'ext-factparse',
      ]],
      'just roots' => [[
        'roots' => ['foo/', 'bar/'],
      ]],
    ];
  }

  <<DataProvider('goodTestCases')>>
  public function testDataLoader(array<string, mixed> $data): void {
    $config = ConfigurationLoader::fromData($data, '/dev/null');
    $this->assertGoodConfig($data, $config);
  }

  <<DataProvider('goodTestCases')>>
  public function testJSONLoader(array<string, mixed> $data): void {
    $config = ConfigurationLoader::fromJSON(
      \json_encode($data),
      '/dev/null',
    );
    $this->assertGoodConfig($data, $config);
  }

  <<DataProvider('goodTestCases')>>
  public function testFileLoader(array<string, mixed> $data): void {
    $fname = \tempnam(\sys_get_temp_dir(), 'testjson');
    try {
      \file_put_contents(
        $fname,
        \json_encode($data),
      );
      $config = ConfigurationLoader::fromFile($fname);
      $this->assertGoodConfig($data, $config);
    } finally {
      \unlink($fname);
    }
  }

  private function assertGoodConfig(
    array<string, mixed> $data,
    Config $config,
  ): void {
    expect(      $config['roots']->toArray(),
)->toBePHPEqual(
      $data['roots']    );

    expect(      AutoloadFilesBehavior::coerce($config['autoloadFilesBehavior'])
)->toNotBeNull(
    );

    $config = Shapes::toArray($config);
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $value = new ImmVector($value);
        expect($config[$key])->toBePHPEqual($value);
      } else {
        expect($config[$key])->toBeSame($value);
      }
    }
  }
}
