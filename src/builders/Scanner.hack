/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\AutoloadMap;

/** Create a `Builder` by scanning file contents.
 */
abstract final class Scanner {
  /** Construct a builder for a single file */
  public static function fromFile(string $path, Parser $parser): Builder {
    switch ($parser) {
      case Parser::EXT_FACTPARSE:
        return FactParseScanner::fromFile($path);
    }
  }

  /** Construct a builder for a tree */
  public static function fromTree(string $path, Parser $parser): Builder {
    switch ($parser) {
      case Parser::EXT_FACTPARSE:
        return FactParseScanner::fromTree($path);
    }
  }
}
