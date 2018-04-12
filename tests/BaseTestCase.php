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

abstract class BaseTestCase extends \PHPUnit\Framework\TestCase {
  public function getParsers(): array<(Parser, classname<Builder>)> {
    return [
      tuple(Parser::EXT_FACTPARSE, FactParseScanner::class),
    ];
  }
}
