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

final class RootImporter implements Builder {
  private Vector<Builder> $builders = Vector { };

  public function __construct(
    string $root,
    Config $config,
  ) {
    foreach ($config['roots'] as $tree) {
      $this->builders[] = Scanner::fromTree($tree);
    }

    if (!$config['includeVendor']) {
      return;
    }

    foreach (glob($root.'/vendor/*/*/composer.json') as $composer_json) {
      $this->builders[] = new ComposerImporter($composer_json, $config);
    }
  }

  public function getAutoloadMap(): AutoloadMap {
    return Merger::merge(
      $this->builders->map($builder ==> $builder->getAutoloadMap())
    );
  }

  public function getFiles(): ImmVector<string> {
    $files = Vector { };
    foreach ($this->builders as $builder) {
      $files->addAll($builder->getFiles());
    }
    return $files->toImmVector();
  }
}
