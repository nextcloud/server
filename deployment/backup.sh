#!/usr/bin/env bash
# SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

set -euo pipefail
umask 077

backup_dir="${1:-deployment/backups}"
timestamp="$(date -u +%Y%m%dT%H%M%SZ)-$$"
maintenance_enabled=0
backup_complete=0

mkdir -p "$backup_dir"

cleanup() {
	status=$?
	if [ "$backup_complete" -eq 0 ]; then
		rm -f -- "$backup_dir/database-$timestamp.dump" \
			"$backup_dir/data-$timestamp.tar.gz" \
			"$backup_dir/config-$timestamp.tar.gz" \
			"$backup_dir/custom-apps-$timestamp.tar.gz" \
			"$backup_dir/SHA256SUMS-$timestamp"
	fi
	if [ "$maintenance_enabled" -eq 1 ]; then
		if ! docker compose --file deployment/compose.yaml exec --index=1 -T app php occ maintenance:mode --off; then
			echo "Failed to disable Nextcloud maintenance mode" >&2
			status=1
		fi
	fi
	exit "$status"
}

trap cleanup EXIT
trap 'exit 1' HUP INT TERM

docker compose --file deployment/compose.yaml exec --index=1 -T app php occ maintenance:mode --on
maintenance_enabled=1

docker compose --file deployment/compose.yaml exec -T db sh -ec \
	'pg_dump --username="$POSTGRES_USER" --dbname="$POSTGRES_DB" --format=custom' \
	> "$backup_dir/database-$timestamp.dump"
docker compose --file deployment/compose.yaml exec --index=1 -T app \
	tar -czf - -C /var/www/html/data . > "$backup_dir/data-$timestamp.tar.gz"
docker compose --file deployment/compose.yaml exec --index=1 -T app \
	tar -czf - -C /var/www/html/config . > "$backup_dir/config-$timestamp.tar.gz"
docker compose --file deployment/compose.yaml exec --index=1 -T app \
	tar -czf - -C /var/www/html/custom_apps . > "$backup_dir/custom-apps-$timestamp.tar.gz"

shasum -a 256 "$backup_dir"/database-"$timestamp".dump \
	"$backup_dir"/data-"$timestamp".tar.gz \
	"$backup_dir"/config-"$timestamp".tar.gz \
	"$backup_dir"/custom-apps-"$timestamp".tar.gz > "$backup_dir/SHA256SUMS-$timestamp"
backup_complete=1
