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

/** Create an autoload map for a directory that contains an
 * `hh_autoload.json`.
 *
 * This may be used for the project root, and for any projects in
 * `vendor/` that are designed for use with `hhvm-autoload`.
 */
final class HHImporter implements Builder {
  private Vector<Builder> $builders = Vector { };
  private Vector<string> $files = Vector { };
  private Config $config;

  public function __construct(
    string $root,
    IncludedRoots $included_roots,
  ) {
    $config_file = $root.'/hh_autoload.json';
    if (!\file_exists($config_file)) {
      $roots = (ImmVector { 'src', 'lib' })
        ->filter($x ==> \is_dir($root.'/'.$x));
      $dev_roots = (ImmVector { 'test', 'tests', 'examples', 'example' })
        ->filter($x ==> \is_dir($root.'/'.$x));
      \file_put_contents(
        $config_file,
        \json_encode(
          shape(
            'roots' => $roots,
            'devRoots' => $dev_roots,
            'devFailureHandler' => HHClientFallbackHandler::class,
          ),
          \JSON_PRETTY_PRINT,
        )."\n",
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

    switch($included_roots) {
      case IncludedRoots::PROD_ONLY:
        $roots = $config['roots'];
        break;
      case IncludedRoots::DEV_AND_PROD:
        $roots = $config['roots']->concat($config['devRoots']);
        break;
    }

    foreach ($roots as $tree) {
      if ($tree[0] !== '/') {
        $tree = $root.'/'.$tree;
      }
      $this->builders[] = Scanner::fromTree(
        $tree,
        $config['parser'],
      );
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
