/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\AutoloadMap;

/** Build an autoload map for the project root.
 *
 * This will:
 * - create an `HHImporter` for the current directory
 * - create `HHImporter`s for every project under `vendor/` that has
 *   `hh_autoload.json`
 *
 * Previously we also supported projects without `hh_autoload.json` by
 * simulating Composer's autoload behavior, but we no longer do because that
 * mostly applied to PHP files which HHVM can no longer parse.
 */
final class RootImporter implements Builder {
  private Vector<Builder> $builders = Vector {};
  private HHImporter $hh_importer;

  public function __construct(
    string $root,
    IncludedRoots $included = IncludedRoots::PROD_ONLY,
  ) {
    $this->hh_importer = new HHImporter($root, $included);
    $this->builders[] = $this->hh_importer;
    $config = $this->hh_importer->getConfig();

    if (!$config['includeVendor']) {
      return;
    }

    foreach (\glob($root.'/vendor/*/*/') as $dependency) {
      if (\file_exists($dependency.'/hh_autoload.json')) {
        $this->builders[] = new HHImporter(
          $dependency,
          IncludedRoots::PROD_ONLY,
        );
      }
    }
  }

  public function getAutoloadMap(): AutoloadMap {
    return Merger::merge(
      $this->builders->map($builder ==> $builder->getAutoloadMap()),
    );
  }

  public function getFiles(): ImmVector<string> {
    $files = Vector {};
    foreach ($this->builders as $builder) {
      $files->addAll($builder->getFiles());
    }
    return $files->toImmVector();
  }

  public function getConfig(): Config {
    return $this->hh_importer->getConfig();
  }
}
