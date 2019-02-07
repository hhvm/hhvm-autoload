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
 * - create `ComposerImporter`s or `HHImporter`s for every project under
 *   `vendor/`
 */
final class RootImporter implements Builder {
  private Vector<Builder> $builders = Vector { };
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
        continue;
      }
      $composer_json = $dependency.'/composer.json';
      if (\file_exists($composer_json)) {
        $this->builders[] = new ComposerImporter($composer_json, $config);
        continue;
      }
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

  public function getConfig(): Config {
    return $this->hh_importer->getConfig();
  }
}
