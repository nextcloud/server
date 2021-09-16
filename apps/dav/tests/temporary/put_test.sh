#!/bin/bash

script_path="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

user='admin'
pass='admin'
server='localhost/owncloud'

testfile2="$script_path/zombie.jpg"

blackfire --samples 1 curl -X PUT -u $user:$pass --cookie "XDEBUG_SESSION=MROW4A;path=/;" --data-binary @"$testfile2" "http://$server/remote.php/webdav/test/zombie.jpg"
#curl -X PUT -u $user:$pass --cookie "XDEBUG_SESSION=MROW4A;path=/;" --data-binary @"$testfile2" "http://$server/remote.php/webdav/test/zombie.jpg"
