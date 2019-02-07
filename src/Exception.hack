/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\AutoloadMap;

/** Base class for all exceptions thrown by `hhvm-autoload` */
class Exception extends \Exception {
  public function __construct(
    \HH\FormatString<\PlainSprintf> $format,
    /* HH_FIXME[4033] expected type constraint */
    ...$args
  ) {
    /* HH_FIXME[4027] - the typechecker's printf support doesn't allow
     * passing it along to something else that has validated format
     * strings */
    parent::__construct(\sprintf($format, ...$args));
  }
}
