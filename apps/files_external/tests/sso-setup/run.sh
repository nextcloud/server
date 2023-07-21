#!/usr/bin/env sh
set -e

SCRIPT_DIR="${0%/*}"

DC_IP=$("$SCRIPT_DIR"/start-dc.sh)
"$SCRIPT_DIR"/start-apache.sh "$DC_IP" "$PWD"
"$SCRIPT_DIR"/setup-sso-nc.sh
"$SCRIPT_DIR"/test-sso-smb.sh "$DC_IP"
