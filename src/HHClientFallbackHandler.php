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

/**
 * If a class/function/type isn't in the map, ask hh_client where it is.
 *
 * No op if CI, TRAVIS, or CONTINOUS_INTEGRATION is true.
 */
abstract final class HHClientFallbackHandler extends FailureHandler {
  <<__Memoize, __Override>>
  public static function isEnabled(): bool {
    $killswitches = ImmSet { 'CI', 'TRAVIS', 'CONTINUOUS_INTEGRATION' };
    foreach ($killswitches as $killswitch) {
      $env = \getenv($killswitch);
      if ($env === 'true' || $env === '1') {
        return true;
      }
    }
    return false;
  }

  public static function handleFailedType(string $name): void {
    $file = self::lookupPath('class', $name);
    if ($file === null) {
      $file = self::lookupPath('typedef', $name);
    }

    if ($file === null) {
      return;
    }

    self::requireFile($file);
  }

  public static function handleFailedFunction(string $name): void {
    $file = self::lookupPath('function', $name);
    if ($file === null) {
      return;
    }

    self::requireFile($file);
  }

  public static function handleFailedConstant(string $name): void {
    $file = self::lookupPath('constant', $name);
    if ($file === null) {
      return;
    }

    self::requireFile($file);
  }

  private static function lookupPath(string $kind, string $name): ?string {
    $key = __CLASS__.'!'.$kind.'!'.$name.'!'.self::getBuildID();
    if (\apc_exists($key)) {
      $file = \apc_fetch($key)[1];
      if (\file_exists($file)) {
        return $file;
      }
      \apc_delete($key);
    }
    $path = self::lookupPathImpl($kind, $name);
    \apc_store($key, ['I_AM_NOT_FALSEY', $path]);
    return $path;
  }

  private static function lookupPathImpl(string $kind, string $name): ?string {
    $cmd = (ImmVector { 'hh_client', '--json', '--search-'.$kind, $name })->map(
      $x ==> \escapeshellarg($x),
    );
    $cmd = \implode(' ', $cmd);

    $exit_code = null;
    $output = array();
    $last = \exec($cmd, $output, $exit_code);
    if ($exit_code !== 0) {
      return null;
    }

    $data = \json_decode($last, /* arrays = */ true);
    if (!\is_array($data)) {
      return null;
    }
    foreach ($data as $row) {
      if ($row['name'] === $name) {
        $file = $row['filename'];
        if (\substr($file, -4) === '.hhi') {
          return null;
        }
        return $file;
      }
    }
    return null;
  }

  private static function requireFile(string $path): void {
    /* HH_IGNORE_ERROR[1002] */
    require ($path);
  }
}
