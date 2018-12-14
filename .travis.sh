#!/bin/sh
set -ex
hhvm --version

composer install

bin/hh-autoload
hh_client
hhvm vendor/bin/hacktest tests/*.php
ENABLE_HH_CLIENT_AUTOLOAD=true hhvm vendor/bin/hacktest \
  tests/FallbackHandlerTest.php
