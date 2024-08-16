#!/usr/bin/env bash

# SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

# Helper script to merge several Noto fonts in a single TTF file.
#
# The "Noto Sans" font (https://www.google.com/get/noto) only includes a subset
# of all the available glyphs in the Noto fonts. This scripts uses
# "merge_noto.py" from the Noto Tools package to add other scripts, like Arabic,
# Devanagari or Hebrew.
#
# "merge_noto.py" originally merges the fonts by region. However it was adjusted
# to merge "all" the fonts in a single file, like done by "merge_fonts.py". The
# reason to use "merge_noto.py" instead of "merge_fonts.py" is that
# "merge_noto.py" merges regular and bold fonts, which are both needed in
# Nextcloud. "merge_fonts.py" only merges regular fonts, and adjusting it to
# handle bold fonts too would have been more work than adjusting
# "merge_noto.py".
#
# Please note that, due to technical limitations of the TTF format (a single
# file can not have more than 65535 glyphs) the merged file does not include any
# Chinese, Japanese or Korean glyph (the Noto CJK files already use all the
# slots). In fact, it seems that it can not include either all the glyphs from
# all the non CJK Noto fonts, so it merges only those predefined in the
# "merge_fonts.py" script (as it is a larger set than the original one in
# "merge_noto.py").
#
# Also please note that merging the fonts is a slow process and it can take a
# while (from minutes to hours, depending on the system).
#
# To perform its job, the script requires the "docker" command to be available.
#
# The Docker Command Line Interface (the "docker" command) requires special
# permissions to talk to the Docker daemon, and those permissions are typically
# available only to the root user. Please see the Docker documentation to find
# out how to give access to a regular user to the Docker daemon:
# https://docs.docker.com/engine/installation/linux/linux-postinstall/
#
# Note, however, that being able to communicate with the Docker daemon is the
# same as being able to get root privileges for the system. Therefore, you must
# give access to the Docker daemon (and thus run this script as) ONLY to trusted
# and secure users:
# https://docs.docker.com/engine/security/security/#docker-daemon-attack-surface

# Stops the container started by this script.
function cleanUp() {
	# Disable (yes, "+" disables) exiting immediately on errors to ensure that
	# all the cleanup commands are executed (well, no errors should occur during
	# the cleanup anyway, but just in case).
	set +o errexit

	echo "Cleaning up"
	docker rm --volumes --force $DOCKER_CONTAINER_ID
}

# Exit immediately on errors.
set -o errexit

# Execute cleanUp when the script exits, either normally or due to an error.
trap cleanUp EXIT

# Ensure working directory is script directory, as some actions (like copying
# the patches to the container) expect that.
cd "$(dirname $0)"

# python:3.9 can not be used, as one of the requeriments of Noto Tools
# (pyclipper) fails to build.
#
# The container exits immediately if no command is given, so a Bash session
# is created to prevent that.
DOCKER_CONTAINER_ID=`docker run --rm --detach --interactive --tty python:3.8-slim bash`

# Install required dependencies.
docker exec $DOCKER_CONTAINER_ID apt-get update
docker exec $DOCKER_CONTAINER_ID apt-get install -y git gcc g++ libjpeg-dev zlib1g-dev wget

# Install Noto Tools in the container.
docker exec --workdir /tmp $DOCKER_CONTAINER_ID git clone https://github.com/googlefonts/nototools
docker exec --workdir /tmp/nototools $DOCKER_CONTAINER_ID git checkout 76b29f8f8f9b
docker exec --workdir /tmp/nototools $DOCKER_CONTAINER_ID pip install --requirement requirements.txt
docker exec --workdir /tmp/nototools $DOCKER_CONTAINER_ID pip install --editable .

# As Noto Tools were installed as "editable" the scripts can be patched after
# installation.
docker cp merge-font-noto-fix-merging-v20201206-phase3-76b29f8f8f9b.patch $DOCKER_CONTAINER_ID:/tmp/nototools/merge-font-noto-fix-merging-v20201206-phase3-76b29f8f8f9b.patch
docker exec --workdir /tmp/nototools --interactive $DOCKER_CONTAINER_ID patch --strip 1 < merge-font-noto-fix-merging-v20201206-phase3-76b29f8f8f9b.patch

# Get Noto fonts.
#
# Phase 2 Noto fonts use 2048 units per em, while phase 3 Noto fonts use 1000*.
# Currently the fonts in the released package** (apparently from 2017-10-25) are
# a mix of both, but fonts with different units per em can not be merged***.
# However, the fonts in the Git repository, although not released yet, are all
# using 1000 units per em already, so those are the ones merged.
#
# *https://github.com/googlefonts/noto-fonts/issues/908#issuecomment-298687906.
# **https://noto-website-2.storage.googleapis.com/pkgs/Noto-unhinted.zip
# ***https://fonttools.readthedocs.io/en/latest/merge.html
docker exec --workdir /tmp $DOCKER_CONTAINER_ID wget https://github.com/googlefonts/noto-fonts/archive/v20201206-phase3.tar.gz
docker exec --workdir /tmp $DOCKER_CONTAINER_ID tar -xzf v20201206-phase3.tar.gz

# noto-fonts in Git and snapshots of Git (like the package used) have a
# subdirectory for each font, but "merge_noto.py" expects to find all the fonts
# in a single directory, so the structure needs to be "flattened".
#
# Hinted fonts* adapt better to being rendered in different sizes. The full
# package in https://www.google.com/get/noto/ includes only unhinted fonts
# (according to its name**, I have not actually verified the fonts themselves),
# while the individual fonts listed below in the page are a mix of hinted and
# unhinted fonts. However, the Git directory has hinted versions of all fonts,
# so those are the ones merged (maybe there is a good reason not to merge hinted
# fonts, but seems to work :-P).
#
# *https://en.wikipedia.org/wiki/Font_hinting
# **https://noto-website-2.storage.googleapis.com/pkgs/Noto-unhinted.zip
docker exec --workdir /tmp $DOCKER_CONTAINER_ID mkdir --parent individual/hinted
docker exec --workdir /tmp $DOCKER_CONTAINER_ID find noto-fonts-20201206-phase3/hinted/ttf -iname "NotoSans*Regular.ttf" -exec mv {} individual/hinted/ \;
docker exec --workdir /tmp $DOCKER_CONTAINER_ID find noto-fonts-20201206-phase3/hinted/ttf -iname "NotoSans*Bold.ttf" -exec mv {} individual/hinted/ \;

# Merge the fonts.
docker exec --workdir /tmp $DOCKER_CONTAINER_ID mkdir --parent combined/hinted
docker exec --workdir /tmp $DOCKER_CONTAINER_ID merge_noto.py

# Copy resulting files.
#
# Noto fonts, as well as the merged files, are licensed under the SIL Open Font
# License: https://scripts.sil.org/OFL
docker cp $DOCKER_CONTAINER_ID:/tmp/combined/hinted/NotoSans-Regular.ttf ../core/fonts/NotoSans-Regular.ttf
docker cp $DOCKER_CONTAINER_ID:/tmp/combined/hinted/NotoSans-Bold.ttf ../core/fonts/NotoSans-Bold.ttf
