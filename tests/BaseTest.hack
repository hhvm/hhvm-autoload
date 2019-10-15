/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\AutoloadMap;

abstract class BaseTest extends \Facebook\HackTest\HackTest {
  public function getParsers(): array<(Parser, classname<Builder>)> {
    return varray[
      tuple(Parser::EXT_FACTPARSE, FactParseScanner::class),
    ];
  }
}
