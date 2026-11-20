B=http://localhost:8080
TOKEN=$(cat data/apptoken.txt)
# 1 baseline: existing PROPFIND as uid
curl -s -o /dev/null -X PROPFIND -u test:test $B/remote.php/dav/files/test
# 2 client login with email + app password
curl -s -o /dev/null -X PROPFIND -u "test@example.com:$TOKEN" $B/remote.php/dav/files/test
# 3 client login with email + wrong password (stale client password)
curl -s -o /dev/null -X PROPFIND -u test@example.com:wrong $B/remote.php/dav/files/test
# 4 CalDAV principal search by email
curl -s -o /dev/null -u test:test -X REPORT -H 'Depth: 0' -H 'Content-Type: application/xml' $B/remote.php/dav/principals/users/ --data '<d:principal-property-search xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns"><d:property-search><d:prop><s:email-address/></d:prop><d:match>seed49999@example.com</d:match></d:property-search><d:prop><d:displayname/></d:prop></d:principal-property-search>'
