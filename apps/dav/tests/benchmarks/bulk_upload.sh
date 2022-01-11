#!/bin/bash

set -eu

# bulk_upload.sh <nb-of-files> <size-of-files>

KB=${KB:-100}
MB=${MB:-$((KB*1000))}

NB=$1
SIZE=$2

CONCURRENCY=${CONCURRENCY:-1}
BANDWIDTH=${BANDWIDTH:-$((100*MB/CONCURRENCY))}

USER="admin"
PASS="password"
SERVER="nextcloud.test"
UPLOAD_PATH="/tmp/bulk_upload_request_$(openssl rand --hex 8).txt"
BOUNDARY="boundary_$(openssl rand --hex 8)"
LOCAL_FOLDER="/tmp/bulk_upload/${BOUNDARY}_${NB}_${SIZE}"
REMOTE_FOLDER="/bulk_upload/${BOUNDARY}_${NB}_${SIZE}"

mkdir --parent "$LOCAL_FOLDER"

for ((i=1; i<="$NB"; i++))
do
	file_name=$(openssl rand --hex 8)
	file_local_path="$LOCAL_FOLDER/$file_name.txt"
	file_remote_path="$REMOTE_FOLDER/$file_name.txt"
	head -c "$SIZE" /dev/urandom > "$file_local_path"
	file_mtime=$(stat -c %Y "$file_local_path")
	file_hash=$(md5sum "$file_local_path" | awk '{ print $1 }')
	file_size=$(du -sb "$file_local_path" | awk '{ print $1 }')

	{
		echo -en "--$BOUNDARY\r\n"
		# echo -en "Content-ID: $file_name\r\n"
		echo -en "X-File-Path: $file_remote_path\r\n"
		echo -en "X-OC-Mtime: $file_mtime\r\n"
		# echo -en "X-File-Id: $file_id\r\n"
		echo -en "X-File-Md5: $file_hash\r\n"
		echo -en "Content-Length: $file_size\r\n"
		echo -en "\r\n" >> "$UPLOAD_PATH"

		cat "$file_local_path"
		echo -en "\r\n" >> "$UPLOAD_PATH"
	} >> "$UPLOAD_PATH"
done

echo -en "--$BOUNDARY--\r\n" >> "$UPLOAD_PATH"

echo "Creating folder /bulk_upload"
curl \
	-X MKCOL \
	-k \
	"https://$USER:$PASS@$SERVER/remote.php/dav/files/$USER/bulk_upload" > /dev/null

echo "Creating folder $REMOTE_FOLDER"
curl \
	-X MKCOL \
	-k \
	"https://$USER:$PASS@$SERVER/remote.php/dav/files/$USER/$REMOTE_FOLDER"

echo "Uploading $NB files with total size: $(du -sh "$UPLOAD_PATH" | cut -d '	' -f1)"
echo "Local file is: $UPLOAD_PATH"
curl \
	-X POST \
	-k \
	--progress-bar \
	--limit-rate "${BANDWIDTH}k" \
	--cookie "XDEBUG_PROFILE=true;path=/;" \
	-H "Content-Type: multipart/related; boundary=$BOUNDARY" \
	--data-binary "@$UPLOAD_PATH" \
	"https://$USER:$PASS@$SERVER/remote.php/dav/bulk"

rm -rf "${LOCAL_FOLDER:?}"
rm "$UPLOAD_PATH"
