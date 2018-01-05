#!/bin/sh
set -ex
hhvm --version
curl https://getcomposer.org/installer | hhvm -d hhvm.jit=0 --php -- /dev/stdin --install-dir=/usr/local/bin --filename=composer

cd /var/source
hhvm -d hhvm.jit=0 /usr/local/bin/composer install

hh_server --check $(pwd)
hhvm -d hhvm.jit=0 vendor/bin/phpunit tests/
bin/hh-autoload
hh_server --check $(pwd)
hhvm -d hhvm.jit=0 vendor/bin/phpunit \
  --bootstrap=vendor/hh_autoload.php tests/
hhvm -d hhvm.jit=0 -d hhvm.php7.all=1 vendor/bin/phpunit \
  --bootstrap=vendor/hh_autoload.php tests/

sed -i '/enable_experimental_tc_features/d' .hhconfig
hh_server --check $(pwd)
