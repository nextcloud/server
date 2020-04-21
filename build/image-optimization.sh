#!/usr/bin/env bash

function recursive_optimize_images() {
	cd "$1" || return

	# Optimize all JPGs and PNGs
	optipng -o6 -strip all *.png
	jpegoptim --strip-all *.jpg

	# Optimize all SVGs
	for svg in *.svg
	do
		mv $svg $svg.opttmp;
		scour --create-groups \
			--enable-id-stripping \
			--enable-comment-stripping \
			--shorten-ids \
			--remove-metadata \
			--strip-xml-prolog \
			--no-line-breaks  \
			-i $svg.opttmp \
			-o $svg
	done

	# Remove temporary SVGs
	rm *.opttmp

	# Check all subfolders
	for dir in */
	do
		if [[ -d "$DIR" ]]; then
			recursive_optimize_images "$dir"
			cd ..
		fi
	done
}

recursive_optimize_images ../
