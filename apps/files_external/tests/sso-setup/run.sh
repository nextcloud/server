#!/usr/bin/env bash
set -e

SCRIPT_DIR="${0%/*}"

DC_IP=$("$SCRIPT_DIR"/start-dc.sh)
"$SCRIPT_DIR"/start-apache.sh "$DC_IP" "$PWD" -v "$PWD/$SCRIPT_DIR"/apache-session.conf:/etc/apache2/sites-enabled/000-default.conf
"$SCRIPT_DIR"/setup-sso-nc.sh smb::kerberos_sso_session

"$SCRIPT_DIR"/test-sso-smb-session.sh "$DC_IP"
