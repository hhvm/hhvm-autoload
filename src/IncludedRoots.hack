/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\AutoloadMap;

/** What root directories to include */
enum IncludedRoots: int {
  /** Only include prod-suitable directories (e.g. `src/`) */
  PROD_ONLY = 0;
  /** Additionally include development-only directories (e.g. `test/`) */
  DEV_AND_PROD = 1;
}
