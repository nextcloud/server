#!/bin/bash
set -euo pipefail

user="$1"
spec="$2"

python -m venv venv
source venv/bin/activate
pip install schemathesis==4.1.0

rm data config/config.php -rf

./occ maintenance:install --admin-pass admin
./occ config:system:set auth.bruteforce.protection.enabled --value=false --type=boolean

if [[ "$user" != "admin" ]]; then
	./occ app:disable password_policy
	NC_PASS="$user" ./occ user:add "$user" --password-from-env
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
	--workers 64 \
	--url http://localhost:8080 \
	-H "OCS-APIRequest: true" \
	-H "Accept: application/json" \
	-H "Authorization: Bearer $app_password" \
	--exclude-checks missing_required_header,unsupported_method,ignored_auth
