#!/bin/sh
set -ex
apt update -y
DEBIAN_FRONTEND=noninteractive apt install -y php-cli
hhvm --version
php --version

(
  cd $(mktemp -d)
  curl https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
)
composer install

bin/hh-autoload
cat vendor/autoload.hack
hh_client
vendor/bin/hacktest tests/*.hack
ENABLE_HH_CLIENT_AUTOLOAD=true vendor/bin/hacktest \
  tests/FallbackHandlerTest.hack

# FactParseScanner should work with either value of disable_xhp_element_mangling
hhvm -dhhvm.hack.lang.disable_xhp_element_mangling=false \
  vendor/bin/hacktest tests/ScannerTest.hack
hhvm -dhhvm.hack.lang.disable_xhp_element_mangling=true \
  vendor/bin/hacktest tests/ScannerTest.hack
