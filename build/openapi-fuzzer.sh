#!/usr/bin/env bash

# SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

set -euo pipefail

if [ "$#" -ne 2 ]; then
    echo "Usage ./build/openapi-fuzzer.sh <user> <path/to/openapi.json>"
    exit 1
fi

user="$1"
spec="$(readlink -f "$2")"

python -m venv venv
source venv/bin/activate
pip install schemathesis==4.1.4

rm data config/config.php -rf

./occ maintenance:install --admin-pass admin
./occ config:system:set auth.bruteforce.protection.enabled --value=false --type=boolean

app="$(echo "$spec" | pcregrep -o1 -e "^.+\/apps[^\/]*\/([a-z_]+)\/openapi[a-z-]*\.json$" || echo "")"
if [[ "$app" != "" ]]; then
	./occ app:enable "$app"
fi

if [[ "$user" != "admin" ]]; then
	is_password_policy_available="$(./occ app:list --output json | jq -r .enabled.password_policy)"

	if [[ "$is_password_policy_available" != "null" ]]; then
		./occ app:disable password_policy
	fi

	NC_PASS="$user" ./occ user:add "$user" --password-from-env

	if [[ "$is_password_policy_available" != "null" ]]; then
		./occ app:enable password_policy
	fi
fi

app_password="$(echo "$user" | ./occ user:auth-tokens:add "$user" | tail -n 1)"

# Ensure enough workers will be available to handle all requests
NEXTCLOUD_WORKERS=100 composer serve &> /dev/null &
pid=$!
function cleanup() {
    kill "$pid"
}
trap cleanup EXIT

until curl -s -o /dev/null http://localhost:8080/status.php; do sleep 1s; done

schemathesis run \
	"$spec" \
	--checks all \
	--exclude-checks missing_required_header,unsupported_method \
	--workers auto \
	--url http://localhost:8080 \
	-H "OCS-APIRequest: true" \
	-H "Accept: application/json" \
	-H "Authorization: Bearer $app_password"
