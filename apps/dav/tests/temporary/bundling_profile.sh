#!/bin/bash

script_path="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

user='admin'
pass='admin'
server='localhost/owncloud'
upload="/tmp/upload.txt"


testfile2="$script_path/zombie.jpg"
size2=$(du -sb $testfile2 | awk '{ print $1 }')
md52=$(md5sum $testfile2 | awk '{ print $1 }')

header="<?xml version='1.0' encoding='UTF-8'?>\n
<d:multipart xmlns:d=\"DAV:\">\n
    <d:part>\n
        <d:prop>\n
            <d:oc-path>/test/zombie1.jpg</d:oc-path>\n
            <d:oc-mtime>1476393386</d:oc-mtime>\n
            <d:oc-id>0</d:oc-id>\n
            <d:oc-total-length>$size2</d:oc-total-length>\n
        </d:prop>\n
    </d:part>\n
    <d:part>\n
        <d:prop>\n
            <d:oc-path>/test/zombie2.jpg</d:oc-path>\n
            <d:oc-mtime>1476393386</d:oc-mtime>\n
            <d:oc-id>1</d:oc-id>\n
            <d:oc-total-length>$size2</d:oc-total-length>\n
        </d:prop>\n
    </d:part>\n
    <d:part>\n
        <d:prop>\n
            <d:oc-path>/test/zombie3.jpg</d:oc-path>\n
            <d:oc-mtime>1476393386</d:oc-mtime>\n
            <d:oc-id>2</d:oc-id>\n
            <d:oc-total-length>$size2</d:oc-total-length>\n
        </d:prop>\n
    </d:part>\n
    <d:part>\n
        <d:prop>\n
            <d:oc-path>/test/zombie4.jpg</d:oc-path>\n
            <d:oc-mtime>1476393386</d:oc-mtime>\n
            <d:oc-id>3</d:oc-id>\n
            <d:oc-total-length>$size2</d:oc-total-length>\n
        </d:prop>\n
    </d:part>\n
    <d:part>\n
        <d:prop>\n
            <d:oc-path>/test/zombie5.jpg</d:oc-path>\n
            <d:oc-mtime>1476393386</d:oc-mtime>\n
            <d:oc-id>4</d:oc-id>\n
            <d:oc-total-length>$size2</d:oc-total-length>\n
        </d:prop>\n
    </d:part>\n
    <d:part>\n
        <d:prop>\n
            <d:oc-path>/test/zombie6.jpg</d:oc-path>\n
            <d:oc-mtime>1476393386</d:oc-mtime>\n
            <d:oc-id>5</d:oc-id>\n
            <d:oc-total-length>$size2</d:oc-total-length>\n
        </d:prop>\n
    </d:part>\n
    <d:part>\n
        <d:prop>\n
            <d:oc-path>/test/zombie7.jpg</d:oc-path>\n
            <d:oc-mtime>1476393386</d:oc-mtime>\n
            <d:oc-id>6</d:oc-id>\n
            <d:oc-total-length>$size2</d:oc-total-length>\n
        </d:prop>\n
    </d:part>\n
    <d:part>\n
        <d:prop>\n
            <d:oc-path>/test/zombie8.jpg</d:oc-path>\n
            <d:oc-mtime>1476393386</d:oc-mtime>\n
            <d:oc-id>7</d:oc-id>\n
            <d:oc-total-length>$size2</d:oc-total-length>\n
        </d:prop>\n
    </d:part>\n
    <d:part>\n
        <d:prop>\n
            <d:oc-path>/test/zombie9.jpg</d:oc-path>\n
            <d:oc-mtime>1476393386</d:oc-mtime>\n
            <d:oc-id>8</d:oc-id>\n
            <d:oc-total-length>$size2</d:oc-total-length>\n
        </d:prop>\n
    </d:part>\n
    <d:part>\n
        <d:prop>\n
            <d:oc-path>/test/zombie10.jpg</d:oc-path>\n
            <d:oc-mtime>1476393386</d:oc-mtime>\n
            <d:oc-id>9</d:oc-id>\n
            <d:oc-total-length>$size2</d:oc-total-length>\n
        </d:prop>\n
    </d:part>\n
</d:multipart>"
headersize=$(echo -en $header | wc -c)

mdupload=$(md5sum $upload | awk '{ print $1 }')
boundrary="boundary_$mdupload"

#CONTENTS
echo -en "--$boundrary\r\nContent-Type: text/xml; charset=utf-8\r\nContent-Length: $headersize\r\n\r\n" > $upload
echo -en $header >> $upload

echo -en "\r\n--$boundrary\r\nContent-ID: 0\r\n\r\n" >> $upload
cat $testfile2 >> $upload

echo -en "\r\n--$boundrary\r\nContent-ID: 1\r\n\r\n" >> $upload
cat $testfile2 >> $upload

echo -en "\r\n--$boundrary\r\nContent-ID: 2\r\n\r\n" >> $upload
cat $testfile2 >> $upload

echo -en "\r\n--$boundrary\r\nContent-ID: 3\r\n\r\n" >> $upload
cat $testfile2 >> $upload

echo -en "\r\n--$boundrary\r\nContent-ID: 4\r\n\r\n" >> $upload
cat $testfile2 >> $upload

echo -en "\r\n--$boundrary\r\nContent-ID: 5\r\n\r\n" >> $upload
cat $testfile2 >> $upload

echo -en "\r\n--$boundrary\r\nContent-ID: 6\r\n\r\n" >> $upload
cat $testfile2 >> $upload

echo -en "\r\n--$boundrary\r\nContent-ID: 7\r\n\r\n" >> $upload
cat $testfile2 >> $upload

echo -en "\r\n--$boundrary\r\nContent-ID: 8\r\n\r\n" >> $upload
cat $testfile2 >> $upload

echo -en "\r\n--$boundrary\r\nContent-ID: 9\r\n\r\n" >> $upload
cat $testfile2 >> $upload

#END BOUNDRARY
echo -en "\r\n--$boundrary--\r\n" >> $upload

#POST
#curl -X DELETE -u $user:$pass --cookie "XDEBUG_SESSION=MROW4A;path=/;" "http://$server/remote.php/webdav/config.cfg"

blackfire --samples 1 curl -X POST -H "Content-Type: multipart/related; boundary=$boundrary" --cookie "XDEBUG_SESSION=MROW4A;path=/;" \
    --data-binary "@$upload" \
    "http://$user:$pass@$server/remote.php/dav/files/$user"




