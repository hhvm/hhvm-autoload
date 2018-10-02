#!/bin/sh
set -ex
hhvm --version

composer install

hh_client
bin/hh-autoload
hhvm vendor/bin/hacktest tests/*.php
ENABLE_HH_CLIENT_AUTOLOAD=true hhvm vendor/bin/hacktest \
  tests/FallbackHandlerTest.php

echo > .hhconfig
hh_server --check $(pwd)
