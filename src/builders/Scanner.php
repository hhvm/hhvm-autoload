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

abstract final class Scanner {
  public static function fromFile(
    string $path,
    Parser $parser,
  ): Builder {
    switch ($parser) {
      case Parser::EXT_FACTPARSE:
        return FactParseScanner::fromFile($path);
    }
  }

  public static function fromTree(
    string $path,
    Parser $parser,
  ): Builder {
    switch ($parser) {
      case Parser::EXT_FACTPARSE:
        return FactParseScanner::fromTree($path);
    }
  }
}
