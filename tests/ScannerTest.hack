/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\AutoloadMap;

use namespace HH\Lib\Dict;
use type Facebook\HackTest\DataProvider;
use function Facebook\FBExpect\expect;

final class ScannerTest extends BaseTest {
  const string FIXTURES = __DIR__.'/../testdata/fixtures';
  const string HH_ONLY_SRC = self::FIXTURES.'/hh-only/src';
  const string XHP_CLASS_SRC = self::FIXTURES.'/xhp-class';
  const string FIXTURES_PREFIX = "Facebook\\AutoloadMap\\TestFixtures\\";

  <<DataProvider('getParsers')>>
  public function testHHOnly(Parser $parser): void {
    $map = Scanner::fromTree(self::HH_ONLY_SRC, $parser)->getAutoloadMap();

    // Some of the names returned by FactParseScanner are invalid, but we only
    // care about the valid ones being parsed correctly.
    if (\ini_get('hhvm.hack.lang.disable_xhp_element_mangling')) {
      $xhp_classes = keyset[
        'xhp-class-old',
        'xhp-namespace\\xhp-class-old',
      ];
    } else {
      $xhp_classes = keyset[
        'xhp_xhp_class_old',
        'xhp_xhp_namespace__xhp_class_old',
      ];
    }

    $this->assertMapMatches(
      Dict\merge(
        dict[
          'ExampleClassInHH' => 'class_in_hh.hh',
          'ExampleClass' => 'class.php',
          'ExampleEnum' => 'enum.php',
        ],
        Dict\from_keys($xhp_classes, $_ ==> 'xhp_class.php'),
      ),
      $map['class'],
    );

    $this->assertMapMatches(
      dict['example_function' => 'function.php'],
      $map['function'],
    );

    $this->assertMapMatches(
      dict[
        'ExampleType' => 'type.php',
        'ExampleNewtype' => 'newtype.php',
      ],
      $map['type'],
    );

    $this->assertMapMatches(
      dict[
        'FREDEMMOTT_AUTOLOAD_MAP_TEST_FIXTURES_EXAMPLE_CONSTANT' =>
          'constant.php',
      ],
      $map['constant'],
    );
  }

  <<DataProvider('getParsers')>>
  public function testNewXHPClassSyntax(Parser $parser): void {
    if (!\ini_get('hhvm.hack.lang.enable_xhp_class_modifier')) {
      self::markTestSkipped('requires enable_xhp_class_modifier=true');
    }

    $map = Scanner::fromTree(self::XHP_CLASS_SRC, $parser)->getAutoloadMap();

    if (\ini_get('hhvm.hack.lang.disable_xhp_element_mangling')) {
      $xhp_classes = keyset[
        'xhp_class_new',
        'xhp_namespace\\xhp_class_new',
      ];
    } else {
      $xhp_classes = keyset[
        'xhp_class_new',
        'xhp_namespace__xhp_class_new',
      ];
    }

    $this->assertMapMatches(
      Dict\from_keys($xhp_classes, $_ ==> 'xhp_class.php'),
      $map['class'],
      self::XHP_CLASS_SRC,
    );
  }

  <<DataProvider('getParsers')>>
  public function testFromTree(
    Parser $parser,
    classname<Builder> $class,
  ): void {
    $builder = Scanner::fromTree(self::HH_ONLY_SRC, $parser);
    expect(\get_class($builder))->toBeSame($class);
  }

  <<DataProvider('getParsers')>>
  public function testFromFile(
    Parser $parser,
    classname<Builder> $class,
  ): void {
    $builder = Scanner::fromFile(self::HH_ONLY_SRC.'/constant.php', $parser);
    expect(\get_class($builder))->toBeSame($class);
    $map = $builder->getAutoloadMap();
    expect($map['class'])->toBeEmpty();
    expect($map['function'])->toBeEmpty();
    expect($map['type'])->toBeEmpty();
    $this->assertMapMatches(
      dict[
        'FREDEMMOTT_AUTOLOAD_MAP_TEST_FIXTURES_EXAMPLE_CONSTANT' =>
          'constant.php',
      ],
      $map['constant'],
    );
  }

  private function assertMapMatches(
    dict<string, string> $expected,
    dict<string, string> $actual,
    string $path_prefix = self::HH_ONLY_SRC,
  ): void {
    foreach ($expected as $name => $file) {
      $a = $path_prefix.'/'.$file;
      $b = idx($actual, \strtolower(self::FIXTURES_PREFIX.$name)) ??
        idx($actual, self::FIXTURES_PREFIX.$name) ??
        idx($actual, $name);

      expect($b)->toBeSame($a);
    }
  }
}
