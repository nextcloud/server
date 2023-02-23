#!/usr/bin/env bash
#
# Takes a given string, e.g. 'bin/console' or 'docker-compose exec php bin/console'
# and split it by words. For each words, if the target is a file, it is touched.
#
# This allows to implement a similar rule to:
#
# ```Makefile
# bin/php-cs-fixer: vendor
#     touch $@
# ```
#
# Indeed when the rule `bin/php-cs-fixer` is replaced with a docker-compose
# equivalent, it will not play out as nicely.
#
# Arguments:
#   $1 - {string} Command potentially containing a file
#

set -Eeuo pipefail;


readonly ERROR_COLOR="\e[41m";
readonly NO_COLOR="\e[0m";


if [ $# -ne 1 ]; then
    printf "${ERROR_COLOR}Illegal number of parameters.${NO_COLOR}\n";

    exit 1;
fi


readonly FILES="$1";


#######################################
# Touch the given file path if the target is a file and do not create the file
# if does not exist.
#
# Globals:
#   None
#
# Arguments:
#   $1 - {string} File path
#
# Returns:
#   None
#######################################
touch_file() {
    local file="$1";

    if [ -e ${file} ]; then
		touch -c ${file};
	fi
}

for file in ${FILES}
do
    touch_file ${file};
done
