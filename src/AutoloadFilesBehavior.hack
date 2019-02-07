/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\AutoloadMap;

/** How to autoload files that composer is told to always require. */
enum AutoloadFilesBehavior: string {
  /** Scan the files for autoloadable definitions */
  FIND_DEFINITIONS = 'scan';
  /** Always require the files */
  EXEC_FILES = 'exec';
}
