#!/bin/bash
#
# SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
#
set -eu

# benchmark.sh

export KB=1000
export MB=$((KB*1000))

MAX_UPLOAD_SIZE=$((512*KB))

export CONCURRENCY=5
export BANDWIDTH=$((100*MB/CONCURRENCY))

FILE_SIZES=($((1*KB)) $((10*KB)) $((100*KB)))

echo "Concurrency: $CONCURRENCY"
echo "Bandwidth: $BANDWIDTH"

md_output="# Bulk upload benchmark\n"
md_output+="\n"
md_output+="- Concurrency: $CONCURRENCY\n"
md_output+="- Bandwidth: ${BANDWIDTH}B\n"
md_output+="\n"
md_output+="| Nb | Size (B) | Bundle (sec) | Single (sec) |\n"
md_output+="|---|---|---|---|\n"

requests_count='1 2 3 4 5'

for size in "${FILE_SIZES[@]}"
do
	nb=$((MAX_UPLOAD_SIZE/size))

	echo "- Upload of $nb tiny file of ${size}B"
	echo "	- Bundled"
	start=$(date +%s)
	echo "$requests_count" | xargs -d ' ' -P $CONCURRENCY -I{} ./bulk_upload.sh "$nb" "$size"
	end=$(date +%s)
	bulk_exec_time=$((end-start))
	echo "${bulk_exec_time}s"

	echo "	- Single"
	start=$(date +%s)
	echo "$requests_count" | xargs -d ' ' -P $CONCURRENCY -I{} ./single_upload.sh "$nb" "$size"
	end=$(date +%s)
	single_exec_time=$((end-start))
	echo "${single_exec_time}s"

	md_output+="| $nb | $size | $bulk_exec_time | $single_exec_time |\n"
done

echo -en "$md_output"