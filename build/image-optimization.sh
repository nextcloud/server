#!/usr/bin/env bash

set -e

OPTIPNG=$(which optipng)
if ! [ -x "$OPTIPNG" ]; then
	echo -e "\033[0;31moptipng executable not found, please install\033[0m" >&2
	exit 1
fi
JPEGOPTIM=$(which jpegoptim)
if ! [ -x "$JPEGOPTIM" ]; then
	echo -e "\033[0;31mjpegoptim executable not found, please install\033[0m" >&2
	exit 2
fi
SCOUR=$(which scour)
if ! [ -x "$SCOUR" ]; then
	echo -e "\033[0;31mscour executable not found, please install\033[0m" >&2
	exit 3
fi

REQUIRED_SCOUR_VERSION="0.38.2"
SCOUR_VERSION=$(scour --version)
if dpkg --compare-versions $SCOUR_VERSION lt $REQUIRED_SCOUR_VERSION; then
	echo "scour version $REQUIRED_SCOUR_VERSION or higher is required, found $SCOUR_VERSION" >&2
	exit 3
fi

set +e

CHECK_DIR='../'
if [[ -d "$1" ]]; then
	CHECK_DIR=$1
fi

function recursive_optimize_images() {
	cd "$1" || return
	DIR_NAME=${PWD##*/}

	if [[ "$DIR_NAME" == "3rdparty" ]]; then
		echo -e "\033[0;36mIgnoring 3rdparty for image optimization\033[0m"
		return
	elif [[ "$DIR_NAME" == "build" ]]; then
		echo -e "\033[0;36mIgnoring build for image optimization\033[0m"
		return
	elif [[ "$DIR_NAME" == "cypress" ]]; then
		echo -e "\033[0;36mIgnoring cypress for image optimization\033[0m"
		return
	elif [[ "$DIR_NAME" == "node_modules" ]]; then
		echo -e "\033[0;36mIgnoring node_modules for image optimization\033[0m"
		return
	elif [[ "$DIR_NAME" == "tests" ]]; then
		echo -e "\033[0;36mIgnoring tests for image optimization\033[0m"
		return
	elif [[ "$DIR_NAME" == "vendor" ]]; then
		echo -e "\033[0;36mIgnoring vendor for image optimization\033[0m"
		return
	elif [[ "$DIR_NAME" == "vendor-bin" ]]; then
		echo -e "\033[0;36mIgnoring vendor-bin for image optimization\033[0m"
		return
	fi

	# Optimize all PNGs
	for png in *.png
	do
		[[ -e "$png" ]] || break

		$OPTIPNG -o6 -strip all "$png"
	done

	# Optimize all JPGs
	for jpg in *.jpg
	do
		[[ -e "$jpg" ]] || break

		$JPEGOPTIM --strip-all "$jpg"
	done

	# Optimize all SVGs
	for svg in *.svg
	do
		[[ -e "$svg" ]] || break

		if [[ "$svg" == "default-source.svg" ]]; then
			echo -e "\033[0;36mIgnoring $svg image optimization\033[0m"
			continue
		fi

		mv $svg $svg.opttmp
		$SCOUR --create-groups \
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
			if git check-ignore $dir -q ; then
				echo -e "\033[0;36m$dir is not shipped. Ignoring image optimization\033[0m"
				continue
			fi

			recursive_optimize_images "$dir"
			cd ..
		fi
	done
}

recursive_optimize_images "$CHECK_DIR"
