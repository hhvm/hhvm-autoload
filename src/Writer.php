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

final class Writer {
  private ?ImmVector<string> $files;
  private ?AutoloadMap $map;
  private ?string $root;
  private bool $relativeAutoloadRoot = true;
  private ?string $failureHandler;

  public function setFailureHandler(?string $handler): this {
    $this->failureHandler = $handler;
    return $this;
  }

  public function setFiles(ImmVector<string> $files): this {
    $this->files = $files;
    return $this;
  }

  public function setAutoloadMap(AutoloadMap $map): this {
    $this->map = $map;
    return $this;
  }

  public function setBuilder(Builder $builder): this {
    $this->files = $builder->getFiles();
    $this->map = $builder->getAutoloadMap();
    return $this;
  }

  public function setRoot(string $root): this {
    $this->root = realpath($root);
    return $this;
  }

  public function setRelativeAutoloadRoot(bool $relative): this {
    $this->relativeAutoloadRoot = $relative;
    return $this;
  }

  public function writeToFile(
    string $destination_file,
  ): this {
    $files = $this->files;
    $map = $this->map;

    if ($files === null) {
      throw new Exception('Call setFiles() before writeToFile()');
    }
    if ($map === null) {
      throw new Exception('Call setAutoloadMap() before writeToFile()');
    }

    if ($this->relativeAutoloadRoot) {
      $root = '__DIR__.\'/../\'';
      $requires = $files->map(
        $file ==> '__DIR__.'.var_export(
          '/../'.$this->relativePath($file),
          true,
        ),
      );
    } else {
      $root = var_export($this->root.'/', true);
      $requires = $files->map(
        $file ==> var_export(
          $this->root.'/'.$this->relativePath($file),
          true,
        ),
      );
    }

    $requires = implode(
      "\n",
      $requires->map($require ==> 'require_once('.$require.');'),
    );

    $map = array_map(
      function ($sub_map): mixed {
        assert(is_array($sub_map));
        return array_map(
          $path ==> $this->relativePath($path),
          $sub_map,
        );
      },
      Shapes::toArray($map),
    );

    $failure_handler = $this->failureHandler;
    if ($failure_handler !== null) {
      if (substr($failure_handler, 0, 1) !== '\\') {
        $failure_handler = '\\'.$failure_handler;
      }
    }

    if ($failure_handler !== null) {
      $add_failure_handler = sprintf(
        "if (%s::isEnabled()) {\n".
        "  \HH\autoload_set_paths(map(), root());\n".
        "  \$handler = new %s();\n".
        "  \$map['failure'] = inst_meth(\$handler, 'handleFailure');\n".
        "} else {\n".
        "  \$handler = null;\n".
        "}\n",
        $failure_handler,
        $failure_handler,
      );
      $init_failure_handler = "\$handler?->initialize();\n";
    } else {
      $add_failure_handler = null;
      $init_failure_handler = null;
    }

    $build_id = var_export(
      date(\DateTime::ATOM).'!'.bin2hex(random_bytes(16)),
      true,
    );

    $map = var_export($map, true);
    $code = <<<EOF
<?hh

/// Generated file, do not edit by hand ///

namespace Facebook\AutoloadMap\Generated;

use Facebook\AutoloadMap\AutoloadMap;

/* HH_IGNORE_ERROR[2012] hhi conflict */
function build_id(): string {
  return $build_id;
}

/* HH_IGNORE_ERROR[2012] hhi conflict */
function root(): string {
  return $root;
}

/* HH_IGNORE_ERROR[2012] hhi conflict */
function map() {
  return $map;
}

$requires

\$map = map();

$add_failure_handler

foreach (\spl_autoload_functions() as \$autoloader) {
  \spl_autoload_unregister(\$autoloader);
}

\HH\autoload_set_paths(\$map, root());

$init_failure_handler
EOF;
    file_put_contents(
      $destination_file,
      $code,
    );

    return $this;
  }

  <<__Memoize>>
  private function relativePath(
    string $path,
  ): string {
    $root = $this->root;
    if ($root === null) {
      throw new Exception('Call setRoot() before writeToFile()');
    }
    $path = realpath($path);
    if (strpos($path, $root) !== 0) {
      throw new Exception(
        "%s is outside root %s",
        $path,
        $root,
      );
    }
    return substr($path, strlen($root) + 1);
  }
}
