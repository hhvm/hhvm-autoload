#!/bin/sh
set -ex
hhvm --version
apt-get update -y
apt-get install -y wget curl git
curl https://getcomposer.org/installer | hhvm -d hhvm.jit=0 --php -- /dev/stdin --install-dir=/usr/local/bin --filename=composer

cd /var/source
hhvm -d hhvm.jit=0 /usr/local/bin/composer install

hh_server --check $(pwd)
hhvm -d hhvm.jit=0 vendor/bin/phpunit tests/
bin/hh-autoload
hh_server --check $(pwd)
hhvm -d hhvm.jit=0 vendor/bin/phpunit \
  --bootstrap=vendor/hh_autoload.php tests/

HHVM_VERSION=$(hhvm --php -r 'echo HHVM_VERSION_ID;' 2>/dev/null);
if [ $HHVM_VERSION -ge 32002 ]; then
  hhvm -d hhvm.php7.all=1 -d hhvm.jit=0 vendor/bin/phpunit tests/
fi

if [ $HHVM_VERSION -ge 32200 -a $HHVM_VERSION -lt 32300 ]; then
  echo enable_experimental_tc_features = optional_shape_field >> .hhconfig
  hh_server --check $(pwd)
fi
