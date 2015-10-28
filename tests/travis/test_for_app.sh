#!/bin/bash
#
# ownCloud
#
# @author Thomas Müller
# @copyright 2015 Thomas Müller thomas.mueller@tmit.eu
#

set -e
APP=$1

if git diff ${TRAVIS_COMMIT_RANGE} | grep -- "^+++ b/apps/$APP/"; then
	echo "Executing this test config ...."
else
	echo "Test config is not relevant for this change. terminating"
	exit 1
fi
