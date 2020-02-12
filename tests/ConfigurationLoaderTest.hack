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
  const IGNORED_VALUE = '__ignore__';

  public function goodTestCases(): dict<string, (darray<string, mixed>)> {
    return dict[
      'fully specified' => tuple(darray[
        'autoloadFilesBehavior' => self::IGNORED_VALUE,
        'relativeAutoloadRoot' => false,
        'includeVendor' => false,
        'extraFiles' => varray[],
        'roots' => varray['foo/', 'bar/'],
        'parser' => 'ext-factparse',
      ]),
      'just roots' => tuple(darray['roots' => varray['foo/', 'bar/']]),
    ];
  }

  <<DataProvider('goodTestCases')>>
  public function testDataLoader(array<string, mixed> $data): void {
    $config = ConfigurationLoader::fromData($data, '/dev/null');
    $this->assertGoodConfig($data, $config);
  }

  <<DataProvider('goodTestCases')>>
  public function testJSONLoader(array<string, mixed> $data): void {
    $config = ConfigurationLoader::fromJSON(\json_encode($data), '/dev/null');
    $this->assertGoodConfig($data, $config);
  }

  <<DataProvider('goodTestCases')>>
  public function testFileLoader(array<string, mixed> $data): void {
    $fname = \tempnam(\sys_get_temp_dir(), 'testjson');
    try {
      \file_put_contents($fname, \json_encode($data));
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
    expect(varray($config['roots']))->toBePHPEqual($data['roots']);

    $config = Shapes::toArray($config);
    foreach ($data as $key => $value) {
      if ($value === self::IGNORED_VALUE) {
        expect($config)->toNotContainKey($key);
      } else if (\is_array($value)) {
        $value = vec($value);
        expect(vec($config[$key] as Traversable<_>))->toBePHPEqual($value);
      } else {
        expect($config[$key])->toBeSame($value);
      }
    }
  }
}
