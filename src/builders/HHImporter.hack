/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\AutoloadMap;

use namespace HH\Lib\Vec;

/** Create an autoload map for a directory that contains an
 * `hh_autoload.json`.
 *
 * This may be used for the project root, and for any projects in
 * `vendor/` that are designed for use with `hhvm-autoload`.
 */
final class HHImporter implements Builder {
  private vec<Builder> $builders = vec[];
  private vec<string> $files = vec[];
  private Config $config;

  public function __construct(string $root, IncludedRoots $included_roots) {
    $config_file = $root.'/hh_autoload.json';
    if (!\file_exists($config_file)) {
      $roots = Vec\filter(vec['src', 'lib'], $x ==> \is_dir($root.'/'.$x));
      $dev_roots = Vec\filter(
        vec['test', 'tests', 'examples', 'example'],
        $x ==> \is_dir($root.'/'.$x),
      );
      \file_put_contents(
        $config_file,
        \json_encode(
          shape(
            'roots' => $roots,
            'devRoots' => $dev_roots,
            'devFailureHandler' => HHClientFallbackHandler::class,
          ),
          \JSON_PRETTY_PRINT,
        ).
        "\n",
      );
      \fprintf(
        \STDERR,
        "An hh_autoload.json is required; a skeleton has been written to %s.\n".
        "If changes are needed, run vendor/bin/hh-autoload after editing.\n".
        "\n".
        "*** WARNING ***\n".
        "This project will not work correctly unless vendor/hh_autoload.php is required.\n".
        "*** WARNING ***\n".
        "\n",
        $config_file,
      );
    }
    $config = ConfigurationLoader::fromFile($config_file);
    $this->config = $config;

    switch ($included_roots) {
      case IncludedRoots::PROD_ONLY:
        $roots = $config['roots'];
        break;
      case IncludedRoots::DEV_AND_PROD:
        $roots = Vec\concat($config['roots'], $config['devRoots']);
        break;
    }

    foreach ($roots as $tree) {
      if ($tree[0] !== '/') {
        $tree = $root.'/'.$tree;
      }
      $this->builders[] = Scanner::fromTree($tree, $config['parser']);
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
      Vec\map($this->builders, $builder ==> $builder->getAutoloadMap()),
    );
  }

  public function getFiles(): vec<string> {
    return Vec\map($this->builders, $builder ==> $builder->getFiles())
      |> Vec\concat(vec[$this->files], $$)
      |> Vec\flatten($$);
  }

  public function getConfig(): Config {
    return $this->config;
  }
}
