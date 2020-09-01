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

# FactParseScanner should work with any combination of enable_xhp_class_modifier
# and disable_xhp_element_mangling
for A in false true; do
  for B in false true; do
    hhvm \
      -dhhvm.hack.lang.enable_xhp_class_modifier=$A \
      -dhhvm.hack.lang.disable_xhp_element_mangling=$B \
      vendor/bin/hacktest tests/ScannerTest.hack
  done
done
