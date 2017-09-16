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
final class HHClientFallbackHandler extends FailureHandler {
  private AutoloadMap $map;
  private bool $dirty = false;

  public function __construct() {
    $this->map = Generated\map();
  }

  public function initialize(): void {
    $file = $this->getCacheFilePath();
    if (!\file_exists($file)) {
      return;
    }
    $data = \json_decode(\file_get_contents($file), /* as array = */ true);
    if ($data === null) {
      \unlink($file);
      return;
    }
    if ($data['build_id'] !== Generated\build_id()) {
      \unlink($file);
      return;
    }
    $map = $data['map'];
    $this->map = $map;
    $map['failure'] = inst_meth($this, 'handleFailure');
    \HH\autoload_set_paths($map, Generated\root());
  }

  public function __destruct() {
    if (!$this->dirty) {
      return;
    }

    file_put_contents(
      $this->getCacheFilePath(),
      json_encode([
        'build_id' => Generated\build_id(),
        'map' => $this->map,
      ], JSON_PRETTY_PRINT),
    );
  }

  private function getCacheFilePath(): string {
    return Generated\root().'/vendor/hh_autoload.hh-cache';
  }

  <<__Memoize, __Override>>
  public function isEnabled(): bool {
    $killswitches = ImmSet { 'CI', 'TRAVIS', 'CONTINUOUS_INTEGRATION' };
    foreach ($killswitches as $killswitch) {
      $env = \getenv($killswitch);
      if ($env === 'true' || $env === '1') {
        return true;
      }
    }
    return false;
  }

  public function handleFailedType(string $name): void {
    $file = $this->lookupPath('class', $name);
    if ($file === null) {
      $file = $this->lookupPath('typedef', $name);
    }

    if ($file === null) {
      return;
    }

    $this->requireFile($file);
  }

  public function handleFailedFunction(string $name): void {
    $file = $this->lookupPath('function', $name);
    if ($file === null) {
      return;
    }

    $this->requireFile($file);
  }

  public function handleFailedConstant(string $name): void {
    $file = $this->lookupPath('constant', $name);
    if ($file === null) {
      return;
    }

    $this->requireFile($file);
  }

  private function lookupPath(string $kind, string $name): ?string {
    static $cache = Map {};
    $key = $kind.'!'.$name;
    if ($cache->containsKey($key)) {
      return $cache[$key];
    }

    $path = $this->lookupPathImpl($kind, $name);
    $cache[$key] = $path;

    if ($path === null) {
      return $path;
    }

    switch ($kind) {
      case 'class':
        $this->map['class'][\strtolower($name)] = $path;
        break;
      case 'type':
        $this->map['type'][\strtolower($name)] = $path;
        break;
      case 'function':
        $this->map['function'][\strtolower($name)] = $path;
        break;
      case 'constant':
        $this->map['constant'][$name] = $path;
        break;
    }
    $this->dirty = true;
    return $path;
  }

  private function lookupPathImpl(string $kind, string $name): ?string {
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

  private function requireFile(string $path): void {
    /* HH_IGNORE_ERROR[1002] */
    require ($path);
  }
}
