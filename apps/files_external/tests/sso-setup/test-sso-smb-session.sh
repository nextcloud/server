#!/usr/bin/env bash
set -e

DC_IP="$1"
SCRIPT_DIR="${0%/*}"

echo -n "Checking that we can authenticate using kerberos: "
LOGIN_CONTENT=$("$SCRIPT_DIR/client-cmd.sh" "$DC_IP" curl -i -s -c /shared/cookie -i -s --negotiate -u testuser@DOMAIN.TEST: --delegation always 'http://httpd.domain.test/index.php/apps/user_saml/saml/login?originalUrl=success&XDEBUG_SESSION_START=1')
if [[ "$LOGIN_CONTENT" =~ "Location: success" ]]; then
  echo "✔️"
else
  echo "❌"
  exit 1
fi

"$SCRIPT_DIR/client-cmd.sh" "$DC_IP" curl -s -b /shared/cookie -c /shared/cookie --negotiate -u testuser@DOMAIN.TEST: --delegation always http://httpd.domain.test/index.php

echo -n "Getting test with session file: "
CONTENT=$("$SCRIPT_DIR/client-cmd.sh" "$DC_IP" curl -s -b /shared/cookie 'http://httpd.domain.test/remote.php/webdav/smb/test.txt?XDEBUG_SESSION_START=1')
CONTENT=$(echo "$CONTENT" | head -n 1 | tr -d '[:space:]')

if [[ $CONTENT == "testfile" ]]; then
  echo "✔️"
else
  echo "❌"
  exit 1
fi
