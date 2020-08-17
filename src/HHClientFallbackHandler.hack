/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\AutoloadMap;

use namespace HH\Lib\{C, Str, Vec};

/**
 * If a class/function/type isn't in the map, ask `hh_client` where it is.
 *
 * This caches the results in APC (falling back to a file) to provide fast
 * workflows.
 *
 * Does nothing if CI, TRAVIS, or CONTINOUS_INTEGRATION is true.
 */
class HHClientFallbackHandler extends FailureHandler {
  private AutoloadMap $map;
  private bool $dirty = false;
  const type TCache = shape(
    'build_id' => string,
    'map' => AutoloadMap,
  );

  public function __construct() {
    $this->map = Generated\map();
  }

  /** Retrieve the cached autoload map.
   *
   * This will try to retrieve a cached map from APC, and if that fails,
   * a cache file.
   */
  protected function getCache(): ?self::TCache {
    $key = __CLASS__.'!cache';
    if (\apc_exists($key)) {
      $success = false;
      $data = \apc_fetch($key, inout $success);
      if (!$success) {
        return null;
      }
      return $data;
    }
    $file = $this->getCacheFilePath();
    if (!\file_exists($file)) {
      return null;
    }

    $data = \json_decode(
      \file_get_contents($file),
      /* as array = */ true,
    );
    if ($data === null) {
      $this->dirty = true;
      \unlink($file);
      return null;
    }

    return $data;
  }

  /** Store a cached map in APC and file.
   *
   * If the file is not writable, it will only be stored in APC.
   */
  protected function storeCache(self::TCache $data): void {
    \apc_store(__CLASS__.'!cache', $data);

    if (!\is_writable(\dirname($this->getCacheFilePath()))) {
      return;
    }

    \file_put_contents(
      $this->getCacheFilePath(),
      \json_encode($data, \JSON_PRETTY_PRINT),
    );
  }

  <<__Override>>
  public function initialize(): void {
    $data = $this->getCache();
    if ($data === null) {
      return;
    }
    if ($data['build_id'] !== Generated\build_id()) {
      $this->dirty = true;
      return;
    }
    $map = $data['map'];
    $this->map = $map;
    $map['failure'] = inst_meth($this, 'handleFailure');
    \HH\autoload_set_paths(
      /* HH_IGNORE_ERROR[4110] incorrect hhi */ $map,
      Generated\root(),
    );

    \register_shutdown_function(() ==> $this->storeCacheIfDirtyDirty());
  }

  private function storeCacheIfDirtyDirty(): void {
    if (!$this->dirty) {
      return;
    }
    $data = shape(
      'build_id' => Generated\build_id(),
      'map' => $this->map,
    );
    $this->storeCache($data);
  }

  /** Where to store the file cache */
  protected function getCacheFilePath(): string {
    return Generated\root().'/vendor/hh_autoload.hh-cache';
  }

  /** Whether or not to use `hh_client`.
   *
   * Defaults to true, unless we're on a common CI platform.
   */
  <<__Override>>
  public static function isEnabled(): bool {
    $force = \getenv('ENABLE_HH_CLIENT_AUTOLOAD');
    if ($force === 'true' || $force === '1') {
      return true;
    }
    if ($force === 'false' || $force === '0') {
      return false;
    }

    $killswitches = keyset['CI', 'TRAVIS', 'CONTINUOUS_INTEGRATION'];
    foreach ($killswitches as $killswitch) {
      $env = \getenv($killswitch);
      if ($env === 'true' || $env === '1') {
        return false;
      }
    }
    return true;
  }

  <<__Override>>
  public function handleFailedType(string $name): void {
    $file = $this->lookupPath('class', $name);
    if ($file === null) {
      if (Str\slice($name, 0, 4) === 'xhp_') {
        $xhp_name = ':'.
          Str\replace_every(Str\slice($name, 4), dict['__' => ':', '_' => '-']);
        $file = $this->lookupPath('class', $xhp_name);
      }

      if ($file === null) {
        $file = $this->lookupPath('typedef', $name);
      }
    }

    if ($file === null) {
      return;
    }

    $this->requireFile($file);
  }

  <<__Override>>
  public function handleFailedFunction(string $name): void {
    $file = $this->lookupPath('function', $name);
    if ($file === null) {
      return;
    }

    $this->requireFile($file);
  }

  <<__Override>>
  public function handleFailedConstant(string $name): void {
    $file = $this->lookupPath('constant', $name);
    if ($file === null) {
      return;
    }

    $this->requireFile($file);
  }

  static dict<string, ?string> $cache = dict[];

  private function lookupPath(string $kind, string $name): ?string {
    $key = $kind.'!'.$name;
    if (C\contains_key(static::$cache, $key)) {
      return static::$cache[$key];
    }

    $path = $this->lookupPathImpl($kind, $name);
    static::$cache[$key] = $path;

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
    $cmd = Vec\map(
      vec['hh_client', '--json', '--search-'.$kind, $name],
      $x ==> \escapeshellarg($x),
    );
    $cmd = \implode(' ', $cmd);

    $exit_code = null;
    $_output = varray[];
    $last = \exec($cmd, inout $_output, inout $exit_code);
    if ($exit_code !== 0) {
      return null;
    }

    $data = \json_decode($last, /* assoc = */ true);
    if (!$data is Traversable<_>) {
      return null;
    }
    foreach ($data as $row) {
      $row as KeyedContainer<_, _>;
      if ($row['name'] === $name) {
        $file = $row['filename'] as ?string;
        if ($file is null || \substr($file, -4) === '.hhi') {
          return null;
        }
        return $file;
      }
    }
    return null;
  }

  private function requireFile(string $path): void {
    require $path;
  }
}
