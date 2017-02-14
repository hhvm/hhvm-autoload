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

final class HHImporter implements Builder {
  private Vector<Builder> $builders = Vector { };
  private Vector<string> $files = Vector { };
  private Config $config;

  public function __construct(
    string $root,
  ) {
    $config_file = $root.'/hh_autoload.json';
    $config = ConfigurationLoader::fromFile($config_file);
    $this->config = $config;

    foreach ($config['roots'] as $tree) {
      if ($tree[0] !== '/') {
        $tree = $root.'/'.$tree;
      }
      $this->builders[] = Scanner::fromTree($tree);
    }

    foreach ($config['extraFiles'] as $file) {
      if ($file[0] !== '/') {
        $file = $root.'/'.$file;
      }
      $this->files[] = $file;
    }
  }

  public function getAutoloadMap(): AutoloadMap {
    return Merger::merge(
      $this->builders->map($builder ==> $builder->getAutoloadMap())
    );
  }

  public function getFiles(): ImmVector<string> {
    $files = Vector { };
    $files->addAll($this->files);
    foreach ($this->builders as $builder) {
      $files->addAll($builder->getFiles());
    }
    return $files->toImmVector();
  }

  public function getConfig(): Config {
    return $this->config;
  }
}
