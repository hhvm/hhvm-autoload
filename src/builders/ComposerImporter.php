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

final class ComposerImporter implements Builder {
  private string $root;
  private Vector<Builder> $builders = Vector { };
  private Vector<string> $files = Vector { };

  public function __construct(
    string $path,
    private Config $config,
  ) {
    if (!file_exists($path)) {
      throw new Exception(
        '%s does not exist',
        $path,
      );
    }
    $this->root = dirname($path);
    $composer_json = file_get_contents($path);
    $composer_config = json_decode(
      $composer_json,
      /* as array = */ true,
    );
    $composer_autoload = idx($composer_config, 'autoload');
    if ($composer_autoload === null) {
      return;
    }
  
    foreach ($composer_autoload as $key => $values) {
      switch ($key) {
        case 'classmap':
          $this->importClassmap($values);
          break;
        case 'files':
          $this->importFiles($values);
          break;
        default:
          throw new Exception(
            "Don't understand how to deal with autoload section %s",
            $key,
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
        $builder ==> $builder->getAutoloadMap()
      )
    );
  }

  private function importClassmap(array<string> $roots): void {
    foreach ($roots as $root) {
      $this->builders[] = new ClassesOnlyFilter(
        Scanner::fromTree($this->root.'/'.$root)
      );
    }
  }

  private function importFiles(array<string> $files): void {
    foreach ($files as $file) {
      $file = $this->root.'/'.$file;
      if (
        $this->config['autoloadFilesBehavior']
        === AutoloadFilesBehavior::FIND_DEFINITIONS
      ) {
        $this->builders[] = Scanner::fromFile($file);
      } else {
        $this->files[] = $file;
      }
    }
  }
}
