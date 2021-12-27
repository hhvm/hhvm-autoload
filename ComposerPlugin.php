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
use Composer\Util\ProcessExecutor;
use Symfony\Component\Process\ExecutableFinder;

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
    $vendor = $composer->getConfig()->get('vendor-dir');

    $this->vendor = $vendor;
    $this->root = dirname($vendor);
  }

  /** Tell composer what events we're interested in.
   *
   * In this case, we want to run whenever composer's own autoload map is updated.
   */
  public static function getSubscribedEvents() {
    return [ScriptEvents::POST_AUTOLOAD_DUMP => [['onPostAutoloadDump', 0]]];
  }

  /** Callback for after the main composer autoload map has been updated.
   *
   * Here we update our autoload map.
   */
  public function onPostAutoloadDump(Event $event) {
    $args = $event->isDevMode() ? '' : ' --no-dev';
    $executor = new ProcessExecutor($this->io);
    $command = ProcessExecutor::escape($this->vendor.'/bin/hh-autoload.hack').
      $args;
    $executor->execute($command);
  }

  /** Does nothing but required by Composer 2.0 */
  public function deactivate(Composer $composer, IOInterface $io) {
  }

  /** Does nothing but required by Composer 2.0 */
  public function uninstall(Composer $composer, IOInterface $io) {
  }
}
