<?php
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

/**************************************/
/***** THIS FILE IS PHP, NOT HACK *****/
/**************************************/

namespace Facebook\AutoloadMap;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

/** Plugin for PHP composer to automatically update the autoload map whenever
 * dependencies are changed or updated.
 */
final class ComposerPlugin
  implements PluginInterface, EventSubscriberInterface {

  private $vendor;
  private $root;
  private $io;

  /** Initialize members */
  public function activate(Composer $composer, IOInterface $io) {
    $this->io = $io;
    $vendor = $composer->getConfig()->get('vendor-dir', '/');

    $this->vendor = $vendor;
    $this->root = dirname($vendor);
  }

  /** Tell composer what events we're interested in.
   *
   * In this case, we want to run whenever composer's own autoload map is updated.
   */
  public static function getSubscribedEvents() {
    return [
      ScriptEvents::POST_AUTOLOAD_DUMP => [
        ['onPostAutoloadDump', 0],
      ],
    ];
  }

  /** Callback for after the main composer autoload map has been updated.
   *
   * Here we update our autoload map.
   */
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

    $handler = $event->isDevMode()
      ? $importer->getConfig()['devFailureHandler']
      : $importer->getConfig()['failureHandler'];

    $this->debugMessage("Writing hh_autoload.php");
    (new Writer())
      ->setBuilder($importer)
      ->setRoot($this->root)
      ->setRelativeAutoloadRoot($importer->getConfig()['relativeAutoloadRoot'])
      ->setFailureHandler($handler)
      ->setIsDev($event->isDevMode())
      ->writeToFile($this->vendor.'/hh_autoload.php');
  }

  private function debugMessage(\HH\string $message) {
    if ($this->io->isDebug()) {
      $this->io->write('hhvm-autoload: '.$message);
    }
  }
}
