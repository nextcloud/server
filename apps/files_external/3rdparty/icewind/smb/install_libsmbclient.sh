#!/usr/bin/env bash

sudo apt-get install libsmbclient-dev libsmbclient
wget -O /tmp/libsmbclient-php.zip https://github.com/eduardok/libsmbclient-php/archive/master.zip
unzip /tmp/libsmbclient-php.zip -d /tmp
cd /tmp/libsmbclient-php-master
phpize && ./configure && make && sudo make install
echo 'extension="libsmbclient.so"' >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
