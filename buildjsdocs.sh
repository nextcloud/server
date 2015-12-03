#!/usr/bin/env bash
#
# ownCloud
#
# Run JS tests
#
# @author Vincent Petry
# @copyright 2014 Vincent Petry <pvince81@owncloud.com>
#
NPM="$(which npm 2>/dev/null)"
PREFIX="build"
OUTPUT_DIR="build/jsdocs"

JS_FILES="core/js/*.js core/js/**/*.js apps/*/js/*.js"

if test -z "$NPM"
then
	echo 'Node JS >= 0.8 is required to build the documentation' >&2
	exit 1
fi

# update/install test packages
mkdir -p "$PREFIX" && $NPM install --link --prefix "$PREFIX" jsdoc || exit 3

JSDOC_BIN="$(which jsdoc 2>/dev/null)"

# If not installed globally, try local version
if test -z "$JSDOC_BIN"
then
	JSDOC_BIN="$PREFIX/node_modules/jsdoc/jsdoc.js"
fi

if test -z "$JSDOC_BIN"
then
	echo 'jsdoc executable not found' >&2
	exit 2
fi

mkdir -p "$OUTPUT_DIR"

NODE_PATH="$PREFIX/node_modules" $JSDOC_BIN -d "$OUTPUT_DIR" $JS_FILES

