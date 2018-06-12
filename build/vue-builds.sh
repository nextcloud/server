#!/bin/bash

root=$(pwd)
entryFile=$1

if [ ! -f "$entryFile" ]
then
	echo "The build file $entryFile does not exists"
	exit 2
else
	backupFile="$entryFile.orig"
	path=$(dirname "$entryFile")

	# Backup original file
	echo "Backing up $entryFile to $backupFile"
	cp $entryFile $backupFile

	# Make the app
	set -e
	cd "$path/../"
	make

	# Reset
	cd $root

	# Compare build files
	echo "Comparing $entryFile to the original"
	if ! diff -q $entryFile $backupFile &>/dev/null
	then
		echo "$entryFile build is NOT up-to-date! Please send the proper production build within the pull request"
		cat $HOME/.npm/_logs/*.log
		exit 2
	else
		echo "$entryFile build is up-to-date"
	fi
fi
