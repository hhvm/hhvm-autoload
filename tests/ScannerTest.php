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

final class ScannerTest extends BaseTestCase {
  const string FIXTURES = __DIR__.'/fixtures';
  const string HH_ONLY_SRC = self::FIXTURES.'/hh-only/src';
  const string FIXTURES_PREFIX =
    "Facebook\\AutoloadMap\\TestFixtures\\";

  /**
   * @dataProvider getParsers
   */
  public function testHHOnly(Parser $parser): void {
    $map = Scanner::fromTree(
      self::HH_ONLY_SRC,
      $parser,
    )->getAutoloadMap();

    $this->assertMapMatches(
      [
        'ExampleClassInHH' => 'class_in_hh.hh',
        'ExampleClass' => 'class.php',
        'ExampleEnum' => 'enum.php',
        'xhp_example__xhp_class' => 'xhp_class.php',
      ],
      $map['class'],
    );

    $this->assertMapMatches(
      [ 'example_function' => 'function.php' ],
      $map['function'],
    );

    $this->assertMapMatches(
      [
        'ExampleType' => 'type.php',
        'ExampleNewtype' => 'newtype.php',
      ],
      $map['type'],
    );

    $this->assertMapMatches(
      [
        'FREDEMMOTT_AUTOLOAD_MAP_TEST_FIXTURES_EXAMPLE_CONSTANT'
          => 'constant.php',
      ],
      $map['constant'],
    );
  }

  /**
   * @dataProvider getParsers
   */
  public function testFromTree(
    Parser $parser,
    classname<Builder> $class,
  ): void {
    $builder = Scanner::fromTree(
      self::HH_ONLY_SRC,
      $parser,
    );
    $this->assertSame($class, \get_class($builder));
  }

  /**
   * @dataProvider getParsers
   */
  public function testFromFile(
    Parser $parser,
    classname<Builder> $class,
  ): void {
    $builder = Scanner::fromFile(
      self::HH_ONLY_SRC.'/constant.php',
      $parser,
    );
    $this->assertSame($class, \get_class($builder));
    $map = $builder->getAutoloadMap();
    $this->assertEmpty($map['class']);
    $this->assertEmpty($map['function']);
    $this->assertEmpty($map['type']);
    $this->assertMapMatches(
      [
        'FREDEMMOTT_AUTOLOAD_MAP_TEST_FIXTURES_EXAMPLE_CONSTANT'
          => 'constant.php',
      ],
      $map['constant'],
    );
  }

  private function assertMapMatches(
    array<string, string> $expected,
    array<string, string> $actual,
  ): void {
    foreach ($expected as $name => $file) {
      $a = self::HH_ONLY_SRC.'/'.$file;
      $b =
        idx($actual, \strtolower(self::FIXTURES_PREFIX.$name))
        ?? idx($actual, self::FIXTURES_PREFIX.$name)
        ?? idx($actual, $name);

      $this->assertSame($a, $b);
    }
  }
}
