#!/usr/bin/env bash
set -e

SCRIPT_DIR="${0%/*}"

DC_IP=$(apps/files_external/tests/sso-setup/start-dc.sh)
apps/files_external/tests/sso-setup/start-apache.sh "$DC_IP" "$PWD" -v "$PWD/$SCRIPT_DIR"/apache-session.conf:/etc/apache2/sites-enabled/000-default.conf
apps/files_external/tests/sso-setup/setup-sso-nc.sh smb::kerberos_sso_session

apps/files_external/tests/sso-setup/test-sso-smb-session.sh "$DC_IP"
