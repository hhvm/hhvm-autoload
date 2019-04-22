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
if (hhvm --version | grep -q -- -dev); then
  rm composer.lock
fi
composer install

bin/hh-autoload
cat vendor/autoload.hack
hh_client
vendor/bin/hacktest tests/*.hack
ENABLE_HH_CLIENT_AUTOLOAD=true vendor/bin/hacktest \
  tests/FallbackHandlerTest.hack
