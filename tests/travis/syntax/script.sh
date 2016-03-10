#!/usr/bin/env bash
SCRIPT=`realpath $0`
SCRIPTPATH=`dirname $SCRIPT`


lib/composer/bin/parallel-lint --exclude lib/composer/jakub-onderka/ --exclude 3rdparty/symfony/polyfill-php70/Resources/stubs/ --exclude 3rdparty/patchwork/utf8/src/Patchwork/Utf8/Bootup/ --exclude 3rdparty/paragonie/random_compat/lib/ --exclude lib/composer/composer/autoload_static.php --exclude 3rdparty/composer/autoload_static.php .
