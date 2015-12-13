<?hh // strict
/*
 *  Copyright (c) 2015, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\AutoloadMap;

abstract final class ConfigurationLoader {
  public static function fromFile(string $path): Config {
    return self::fromJSON(file_get_contents($path), $path);
  }

  public static function fromJSON(string $json, string $path): Config {
    return self::fromData(
      json_decode($json, /* as array = */ true),
      $path,
    );
  }

  public static function fromData(
    array<string, mixed> $data,
    string $path,
  ): Config {
    if (!array_key_exists('roots', $data)) {
      throw new ConfigurationException(
        'File "%s" does not define "roots"',
        $path,
      );
    }

    $roots_arr = idx($data, 'roots');
    if (!is_array($roots_arr)) {
      throw new ConfigurationException(
        'File "%s" has a "roots" key that is not an array',
        $path,
      );
    }

    $roots = Vector { };
    foreach ($roots_arr as $root) {
      if (!is_string($root)) {
        throw new ConfigurationException(
          'File "%s" has a non-string root',
          $path,
        );
      }
      $roots[] = $root;
    }

    $autoload_files_behavior = AutoloadFilesBehavior::FIND_DEFINITIONS;
    if (array_key_exists('autoloadFilesBehavior', $data)) {
      $value = AutoloadFilesBehavior::coerce(
        $data['autoloadFilesBehavior'],
      );
      if ($value === null) {
        throw new ConfigurationException(
          'File "%s" has an invalid value of autoloadFilesBehavior (%s)'.
          '; valid values are: %s',
          $path,
          var_export($value, true),
          implode(', ', AutoloadFilesBehavior::getValues()),
        );
      }
      $autoload_files_behavior = AutoloadFilesBehavior::assert($value);
    }

    $composer_json_fallback = true;
    if (array_key_exists('composerJsonFallback', $data)) {
      $value = $data['composerJsonFallback'];
      if (!is_bool($value)) {
        throw new ConfigurationException(
          'File "%s" has non-bool value of composerJsonFallback: %s',
          $path,
          var_export($value, true),
        );
      }
      $composer_json_fallback = (bool) $value;
    }

    $include_vendor = true;
    if (array_key_exists('includeVendor', $data)) {
      $value = $data['includeVendor'];
      if (!is_bool($value)) {
        throw new ConfigurationException(
          'File "%s" has non-bool value of includeVendor: %s',
          $path,
          var_export($value, true),
        );
      }
      $include_vendor = (bool) $value;
    }

    return shape(
      'autoloadFilesBehavior' => $autoload_files_behavior,
      'composerJsonFallback' => $composer_json_fallback,
      'includeVendor' => $include_vendor,
      'roots' => $roots->toImmVector(),
    );
  }
}
