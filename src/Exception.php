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

abstract class Exception extends \Exception {
  public function __construct(
    \HH\FormatString<\PlainSprintf> $format,
    array<mixed> ...$args
  ) {
    /* HH_FIXME[4027] - the typechecker's printf support doesn't allow
     * passing it along to something else that has validated format
     * strings */
    parent::__construct(sprintf($format, ...$args));
  }
}
