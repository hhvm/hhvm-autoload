<?php
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

use \__SystemLib\HH\Client\CacheKeys;
use \HH\Client\TypecheckResult;
use \HH\Client\TypecheckStatus;

final class Bootstrap {
  public static function build(): \HH\void {
    require_once(__DIR__.'/unsupported/AutoTypecheck.php');
    __UNSUPPORTED__\AutoTypecheck::disable();
    require_once(__DIR__.'/../vendor/autoload.php');

    $builder = Scanner::fromTree(realpath(__DIR__));
    (new Writer())
      ->setRoot(__DIR__.'/..')
      ->setBuilder($builder)
      ->writeToFile(__DIR__.'/../bootstrap.php');
  }
}
