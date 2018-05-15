<?hh // decl
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\AutoloadMap\Generated;

/** Return a unique ID that changes whenever `hhvm-autoload` updates the map */
function build_id(): string;
/** Return the root directory of the project */
function root(): string;
/** Return the actual autoload map */
function map(): \Facebook\AutoloadMap\AutoloadMap;
/** Return true if the package manager was ran in development mode */
function is_dev(): bool;
