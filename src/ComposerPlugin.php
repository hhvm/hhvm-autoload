<?php
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

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

final class ComposerPlugin
  implements PluginInterface, EventSubscriberInterface {

  private $vendor;
  private $root;
  private $io;

  public function activate(Composer $composer, IOInterface $io) {
    $this->io = $io;
    $vendor = $composer->getConfig()->get('vendor-dir', '/');

    $this->vendor = $vendor;
    $this->root = dirname($vendor);
  }

  public static function getSubscribedEvents() {
    return [
      ScriptEvents::POST_AUTOLOAD_DUMP => [
        ['onPostAutoloadDump', 0],
      ],
    ];
  }

  public function onPostAutoloadDump(Event $event) {
    $this->debugMessage("Disabling AutoTypecheck");
    require_once($this->vendor.'/fredemmott/hack-error-suppressor/src/HackErrorSuppressor.php');
    require_once($this->vendor.'/fredemmott/hack-error-suppressor/src/ScopedHackErrorSuppressor.php');
    $typechecker_guard = new \FredEmmott\ScopedHackErrorSuppressor();

    $this->debugMessage("Loading composer autoload");
    require_once($this->vendor.'/autoload.php');

    $this->debugMessage("Parsing tree");
    $importer = new RootImporter(
      $this->root,
      $event->isDevMode()
        ? IncludedRoots::DEV_AND_PROD
        : IncludedRoots::PROD_ONLY
    );

    $this->debugMessage("Writing hh_autoload.php");
    (new Writer())
      ->setBuilder($importer)
      ->setRoot($this->root)
      ->setRelativeAutoloadRoot($importer->getConfig()['relativeAutoloadRoot'])
      ->writeToFile($this->vendor.'/hh_autoload.php');
  }

  private function debugMessage(\HH\string $message) {
    if ($this->io->isDebug()) {
      $this->io->write('hhvm-autoload: '.$message);
    }
  }
}
