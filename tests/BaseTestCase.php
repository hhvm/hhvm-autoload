<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\AutoloadMap;

abstract class BaseTestCase extends \PHPUnit\Framework\TestCase {
  public function getParsers(): array<(Parser, classname<Builder>)> {
    /* HH_FIXME[2049] HHVM_VERSION not in HHI */
    /* HH_FIXME[4106] HHVM_VERSION not in HHI */
    if (version_compare(HHVM_VERSION, '3.18.0', '>=')) {
      return [
        tuple(Parser::DEFINITION_FINDER, DefinitionFinderScanner::class),
        tuple(Parser::EXT_FACTPARSE, FactParseScanner::class),
      ];
    }
    return [tuple(Parser::DEFINITION_FINDER, DefinitionFinderScanner::class)];
  }
}
