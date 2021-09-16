#!/bin/bash

set -eu

scriptPath="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

user='admin'
pass='password'
server='nextcloud.test'
upload="/tmp/upload.txt"


testFile1="$scriptPath/put_test.sh"
size1=$(du -sb "$testFile1" | awk '{ print $1 }')
# md51=$(md5sum "$testFile1" | awk '{ print $1 }')
id1="0"

testFile2="$scriptPath/screenshot.png"
size2=$(du -sb "$testFile2" | awk '{ print $1 }')
# md52=$(md5sum "$testFile2" | awk '{ print $1 }')
id2="1"

header="<?xml version='1.0' encoding='UTF-8'?>\n
<d:multipart xmlns:d=\"DAV:\">\n
    <d:part>\n
        <d:prop>\n
            <d:oc-path>/put_test.sh</d:oc-path>\n
            <d:oc-mtime>1476393777</d:oc-mtime>\n
            <d:oc-id>$id1</d:oc-id>\n
            <d:oc-total-length>$size1</d:oc-total-length>\n
        </d:prop>\n
    </d:part>\n
    <d:part>\n
        <d:prop>\n
            <d:oc-path>/zombie.jpg</d:oc-path>\n
            <d:oc-mtime>1476393386</d:oc-mtime>\n
            <d:oc-id>$id2</d:oc-id>\n
            <d:oc-total-length>$size2</d:oc-total-length>\n
        </d:prop>\n
    </d:part>\n
</d:multipart>"
headerSize=$(echo -en "$header" | wc -c)

mdUpload=$(md5sum $upload | awk '{ print $1 }')
boundary="boundary_$mdUpload"

#CONTENTS
echo -en "--$boundary\r\nContent-Type: text/xml; charset=utf-8\r\nContent-Length: $headerSize\r\n\r\n" > $upload
echo -en "$header" >> $upload

cat "$upload"
echo -en "\r\n--$boundary\r\nContent-ID: $id1\r\n\r\n" >> $upload
cat "$testFile1" >> $upload

echo -en "\r\n--$boundary\r\nContent-ID: $id2\r\n\r\n" >> $upload
cat "$testFile2" >> $upload

#END boundary
echo -en "\r\n--$boundary--\r\n" >> $upload

#POST
#curl -X DELETE -u $user:$pass --cookie "XDEBUG_SESSION=MROW4A;path=/;" "http://$server/remote.php/webdav/config.cfg"

curl -X POST -k -H "Content-Type: multipart/related; boundary=$boundary" --cookie "XDEBUG_SESSION=MROW4A;path=/;" \
    --data-binary "@$upload" \
    "https://$user:$pass@$server/remote.php/dav/files/bundle"




