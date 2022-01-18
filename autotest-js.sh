#!/usr/bin/env bash
#
# ownCloud
#
# Run JS tests
#
# @author Vincent Petry
# @copyright 2014 Vincent Petry <pvince81@owncloud.com>
#

set -euo pipefail

# create scss test
# We use the deprecated node-sass module for that as the compilation fails with modern modules. See "DEPRECATION WARNING" during execution of this script.
mkdir -p tests/css
for SCSSFILE in core/css/*.scss
do
    FILE=$(basename $SCSSFILE)
    printf "\$webroot:''; @import 'functions.scss'; @import 'variables.scss'; @import '${FILE}';" | ./node_modules/.bin/node-sass --include-path core/css/ > tests/css/${FILE}.css
done

npm run test:jsunit