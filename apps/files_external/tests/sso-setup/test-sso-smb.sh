#!/usr/bin/env bash
#
# SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
#

set -e

DC_IP="$1"
SCRIPT_DIR="${0%/*}"

echo -n "Checking that we can authenticate using kerberos: "
LOGIN_CONTENT=$("$SCRIPT_DIR/client-cmd.sh" $DC_IP curl -i -s --negotiate -u testuser@DOMAIN.TEST: --delegation always http://httpd.domain.test/index.php/apps/user_saml/saml/login?originalUrl=http://localhost/success)

if [[ "$LOGIN_CONTENT" =~ "Location: http://localhost/success" ]]; then
  echo "✔️"
else
  echo "❌"
  exit 1
fi
echo -n "Getting test file: "
CONTENT=$("$SCRIPT_DIR/client-cmd.sh" $DC_IP curl -s --negotiate -u testuser@DOMAIN.TEST: --delegation always http://httpd.domain.test/remote.php/webdav/smb/test.txt)
CONTENT=$(echo $CONTENT | head -n 1 | tr -d '[:space:]')

if [[ $CONTENT == "testfile" ]]; then
  echo "✔️"
else
  echo "❌"
  exit 1
fi
