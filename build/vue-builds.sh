#!/bin/bash

root=$(pwd)
entryFile=$1

if [ ! -f "$entryFile" ]
then
	echo "The build file $entryFile does not exists"
	exit 2
else
	path=$(dirname "$entryFile")
	file=$(basename $entryFile)

	set -e
	cd $path
	echo "Entering $path"

	# support for multiple chunks
	for chunk in *$file; do

		# Backup original file
		backupFile="$chunk.orig"
		echo "Backing up $chunk to $backupFile"
		cp $chunk $backupFile

	done

	# Make the app
	echo "Making $file"
	cd ../
	npm --silent install
	npm run --silent build

	# Reset
	cd $root
	cd $path

	# support for multiple chunks
	for chunk in *$file; do

		# Compare build files
		echo "Comparing $chunk to the original"
		backupFile="$chunk.orig"
		if ! diff -q $chunk $backupFile &>/dev/null
		then
			echo "$chunk build is NOT up-to-date! Please send the proper production build within the pull request"
			cat $HOME/.npm/_logs/*.log
			exit 2
		else
			echo "$chunk build is up-to-date"
		fi

	done
fi
