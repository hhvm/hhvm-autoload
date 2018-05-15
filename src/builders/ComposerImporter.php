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

/** Create an autoload map for a directory based on the `composer.json` in that
 * directory.
 *
 * This is used by default for projects in `vendor/` that do not have an
 * `hh_autoload.json` */
final class ComposerImporter implements Builder {
  private string $root;
  private Vector<Builder> $builders = Vector {};
  private Set<string> $excludes = Set {};
  private Vector<string> $files = Vector {};

  public function __construct(string $path, private Config $config) {
    if (!\file_exists($path)) {
      throw new Exception('%s does not exist', $path);
    }
    $this->root = \dirname($path);
    $composer_json = \file_get_contents($path);
    $composer_config = \json_decode($composer_json, /* as array = */ true);
    $composer_autoload = idx($composer_config, 'autoload');
    if ($composer_autoload === null) {
      return;
    }

    foreach ($composer_autoload as $key => $values) {
      switch ($key) {
        case 'psr-0':
          $this->importPSR0($values);
          break;
        case 'psr-4':
          $this->importPSR4($values);
          break;
        case 'classmap':
          $this->importClassmap($values);
          break;
        case 'files':
          $this->importFiles($values);
          break;
        case 'exclude-from-classmap':
          foreach ($values as $value) {
            $this->excludes[] = $this->root.'/'.$value;
          }
          break;
        default:
          throw new Exception(
            "Don't understand how to deal with autoload section %s in %s",
            $key,
            $path,
          );
      }
    }
  }

  public function getFiles(): ImmVector<string> {
    return $this->files->toImmVector();
  }

  public function getAutoloadMap(): AutoloadMap {
    return Merger::merge(
      $this->builders->map(
        $builder ==> new PathExclusionFilter(
          $builder,
          $this->excludes->immutable(),
        ),
      )->map($builder ==> $builder->getAutoloadMap()),
    );
  }

  /** Composer supports detecting classes inside files for given subtrees; this
   * does the same for any directories configured that way.
   */
  private function importClassmap(array<string> $roots): void {
    foreach ($roots as $root) {
      $path = $this->root.'/'.$root;
      try {
        if (\is_dir($path)) {
          $scanner = Scanner::fromTree($path, $this->config['parser']);
        } else {
          $scanner = Scanner::fromFile($path, $this->config['parser']);
        }
      } catch (\UnexpectedValueException $e) {
        // Incorrectly configured configured path.
        continue;
      }

      $this->builders[] = new ClassesOnlyFilter($scanner);
    }
  }

  private function importPSR4(array<string, mixed> $roots): void {
    $roots = self::normalizePSRRoots($roots);
    foreach ($roots as $prefix => $prefix_roots) {
      foreach ($prefix_roots as $root) {
        try {
          $scanner =
            Scanner::fromTree($this->root.'/'.$root, $this->config['parser']);
        } catch (\UnexpectedValueException $e) {
          // Incorrectly configured configured path.
          continue;
        }

        $this->builders[] =
          new PSR4Filter($prefix, $this->root.'/'.$root, $scanner);
      }
    }
  }

  private function importPSR0(array<string, mixed> $roots): void {
    $roots = self::normalizePSRRoots($roots);
    foreach ($roots as $prefix => $prefix_roots) {
      foreach ($prefix_roots as $root) {
        try {
          $scanner =
            Scanner::fromTree($this->root.'/'.$root, $this->config['parser']);
        } catch (\UnexpectedValueException $e) {
          // Incorrectly configured configured path.
          continue;
        }

        $this->builders[] =
          new PSR0Filter($prefix, $this->root.'/'.$root, $scanner);
      }
    }
  }

  private static function normalizePSRRoots(
    array<string, mixed> $roots,
  ): array<string, array<string>> {
    $out = [];
    foreach ($roots as $k => $v) {
      if (is_string($v)) {
        $out[$k][] = $v;
      } else if (is_array($v)) {
        foreach ($v as $w) {
          $out[$k][] = $w;
        }
      }
    }
    return $out;
  }

  private function importFiles(array<string> $files): void {
    foreach ($files as $file) {
      $file = $this->root.'/'.$file;
      if ($this->config['autoloadFilesBehavior'] ===
          AutoloadFilesBehavior::FIND_DEFINITIONS) {
        try {
          $this->builders[] =
            Scanner::fromFile($file, $this->config['parser']);
        } catch (\UnexpectedValueException $e) {
          // Incorrectly configured configured path.
        }
      } else {
        $this->files[] = $file;
      }
    }
  }
}
