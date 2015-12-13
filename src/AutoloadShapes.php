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

type AutoloadMap = shape(
  'class' => ?array<string, string>,
  'function' => ?array<string, string>,
  'type' => ?array<string, string>,
  'failure' => ?(function(string, string):void),
);

// subtype of AutoloadMap
type AutoloadData = shape(
  'root' => string,
  'exec_files' => array<string>,
  'class' => array<string, string>,
  'function' => array<string, string>,
  'type' => array<string, string>,
  'failure' => ?(function(string, string):void),
);
