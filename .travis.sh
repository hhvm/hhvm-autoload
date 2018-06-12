#!/bin/sh
set -ex
hhvm --version

composer install

hh_client
hhvm vendor/bin/phpunit tests/
bin/hh-autoload
hh_client
hhvm vendor/bin/phpunit \
  --bootstrap=vendor/hh_autoload.php tests/

echo > .hhconfig
hh_server --check $(pwd)
