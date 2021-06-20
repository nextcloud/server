#!/usr/bin/env bash

CHECK_DIR='../'
if [[ -d "$1" ]]; then
	CHECK_DIR=$1
fi

function recursive_optimize_images() {
	cd "$1" || return
	DIR_NAME=${PWD##*/}

	if [[ "$DIR_NAME" == "node_modules" ]]; then
		return
	elif [[ "$DIR_NAME" == "tests" ]]; then
		return
	fi

	# Optimize all PNGs
	for png in *.png
	do
		[[ -e "$png" ]] || break

		optipng -o6 -strip all "$png"
	done

	# Optimize all JPGs
	for jpg in *.jpg
	do
		[[ -e "$jpg" ]] || break

		jpegoptim --strip-all "$jpg"
	done

	# Optimize all SVGs
	for svg in *.svg
	do
		[[ -e "$svg" ]] || break

		mv $svg $svg.opttmp
		scour --create-groups \
			--enable-id-stripping \
			--enable-comment-stripping \
			--shorten-ids \
			--remove-metadata \
			--strip-xml-prolog \
			--no-line-breaks  \
			-i $svg.opttmp \
			-o $svg
		rm $svg.opttmp
	done

	# Check all subfolders
	for dir in */
	do
		[[ -e "$dir" ]] || break

		if [[ -d "$dir" ]]; then
			recursive_optimize_images "$dir"
			cd ..
		fi
	done
}

recursive_optimize_images "$CHECK_DIR"
