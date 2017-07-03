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

use FredEmmott\TypeAssert\{
  IncorrectTypeException,
  TypeAssert
};

abstract final class ConfigurationLoader {

  const type TJSONConfig = shape(
    'roots' => array<string>,
    'devRoots' => ?array<string>,
    'autoloadFilesBehavior' => ?AutoloadFilesBehavior,
    'includeVendor' => ?bool,
    'extraFiles' => ?array<string>,
    'parser' => ?Parser,
  );

  public static function fromFile(string $path): Config {
    invariant(
      is_readable($path),
      'Tried to load configuration file %s, but it is not readable.',
      $path,
    );
    return self::fromJSON(file_get_contents($path), $path);
  }

  public static function fromJSON(string $json, string $path): Config {
    $decoded = json_decode($json, /* as array = */ true);
    invariant(
      is_array($decoded),
      'Expected configuration file to contain a JSON object, got %s',
      gettype($decoded),
    );
    return self::fromData(
      $decoded,
      $path,
    );
  }

  public static function fromData(
    array<string, mixed> $data,
    string $path,
  ): Config {
    try {
      $config = TypeAssert::matchesTypeStructure(
        type_structure(self::class, 'TJSONConfig'),
        $data,
      );
    } catch (IncorrectTypeException $_) {
      invariant_violation("Configuration file does not match expected shape");
    }

    return shape(
      'roots' => new ImmVector($config['roots']),
      'devRoots' => self::maybeArrayToImmVector(
        $config['devRoots'] ?? null,
      ),
      'autoloadFilesBehavior' => $config['autoloadFilesBehavior']
        ?? AutoloadFilesBehavior::FIND_DEFINITIONS,
      'includeVendor' => $config['includeVendor'] ?? true,
      'extraFiles' => self::maybeArrayToImmVector(
        $config['extraFiles'] ?? null,
      ),
      'parser' => $config['parser'] ?? self::getDefaultParser(),
    );
  }

  private static function maybeArrayToImmVector<T>(
    ?array<T> $in,
  ): ImmVector<T> {
    if ($in === null) {
      return ImmVector { };
    }
    return new ImmVector($in);
  }

  private static function getDefaultParser(): Parser {
    if (extension_loaded('factparse')) {
      return Parser::EXT_FACTPARSE;
    }
    return Parser::DEFINITION_FINDER;
  }
}
