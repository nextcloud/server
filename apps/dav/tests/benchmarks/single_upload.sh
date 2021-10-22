#!/bin/bash

set -eu

# single_upload.sh <nb-of-files> <size-of-files>

export KB=${KB:-100}
export MB=${MB:-$((KB*1000))}

export NB=$1
export SIZE=$2

export CONCURRENCY=${CONCURRENCY:-1}
export BANDWIDTH=${BANDWIDTH:-$((100*MB/CONCURRENCY))}

export USER="admin"
export PASS="password"
export SERVER="nextcloud.test"
export UPLOAD_ID="single_$(openssl rand --hex 8)"
export LOCAL_FOLDER="/tmp/single_upload/${UPLOAD_ID}_${NB}_${SIZE}"
export REMOTE_FOLDER="/single_upload/${UPLOAD_ID}_${NB}_${SIZE}"

mkdir --parent "$LOCAL_FOLDER"

curl \
	-X MKCOL \
	-k \
	"https://$USER:$PASS@$SERVER/remote.php/dav/files/$USER/bulk_upload" > /dev/null

curl \
	-X MKCOL \
	-k \
	--cookie "XDEBUG_SESSION=true;path=/;" \
	"https://$USER:$PASS@$SERVER/remote.php/dav/files/$USER/$REMOTE_FOLDER"

upload_file() {
	file_name=$(openssl rand --hex 8)
	file_local_path="$LOCAL_FOLDER/$file_name.txt"
	file_remote_path="$REMOTE_FOLDER/$file_name.txt"
	head -c "$SIZE" /dev/urandom > "$file_local_path"

	curl \
		-X PUT \
		-k \
		--limit-rate "${BANDWIDTH}k" \
		--data-binary @"$file_local_path" "https://$USER:$PASS@$SERVER/remote.php/webdav/$file_remote_path"
}
export -f upload_file

file_list=''
for ((i=1; i<"$NB"; i++))
do
	file_list+="$i "
done
file_list+=$NB

echo "$file_list" | xargs -d ' ' -P "$((CONCURRENCY/5))" -I{} bash -c "upload_file {}"

printf "\n"

rm -rf "${LOCAL_FOLDER:?}"/*