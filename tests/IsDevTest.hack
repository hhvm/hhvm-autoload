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
use namespace HH\Lib\{Str, Vec};

final class IsDevTest extends BaseTest {
  public function testIsDevInUnitTest(): void {
    expect(Generated\is_dev())->toBeTrue();
  }

  public function testInStandaloneExecutable(): void {
    $root = __DIR__.'/fixtures/hh-only';
    $builder = new RootImporter($root, IncludedRoots::DEV_AND_PROD);
    $tempfile = \tempnam(\sys_get_temp_dir(), 'hh_autoload.tmp.').'.hack';

    $builder = (new Writer())
      ->setBuilder($builder)
      ->setRoot($root)
      ->setIsDev(true)
      ->writeToFile($tempfile);
    $is_dev = self::exec(
      \PHP_BINARY,
      '-d',
      'auto_prepend_file='.$tempfile,
      $root.'/is_dev.hack',
    );
    expect($is_dev)->toBeSame('bool(true)');

    $builder->setIsDev(false)->writeToFile($tempfile);
    $is_dev = self::exec(
      \PHP_BINARY,
      '-d',
      'auto_prepend_file='.$tempfile,
      $root.'/is_dev.hack',
    );
    expect($is_dev)->toBeSame('bool(false)');

    $is_dev = self::exec(
      '/bin/sh',
      '-c',
      vec[
        \PHP_BINARY,
        '-d',
        'auto_prepend_file='.$tempfile,
        $root.'/is_dev.hack',
      ]
        |> Vec\map($$, $a ==> \escapeshellarg($a))
        |> Str\join($$, ' ')
        |> \escapeshellcmd($$)
        |> 'HH_FORCE_IS_DEV=1 '.$$
    );
    expect($is_dev)->toBeSame('bool(true)');

  }

  private static function exec(string ...$args): string {
    $output = vec[];
    $exit_code = -1;
    Vec\map($args, $arg ==> \escapeshellarg($arg))
      |> Str\join($$, ' ')
      |> \exec($$, inout $output, inout $exit_code);
    $output = Str\join($output, "\n");
    invariant($exit_code === 0, 'exec failed: %s', $output);
    return $output;
  }
}
