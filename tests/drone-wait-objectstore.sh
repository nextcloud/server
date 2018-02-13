#!/bin/bash

function get_swift_token() {
    KEYSTONE_OUT=$(curl -s 'http://dockswift:5000/v2.0/tokens' -H 'Content-Type: application/json' -d '{"auth":{"passwordCredentials":{"username":"swift","password":"swift"},"tenantName":"service"}}')
    if (echo "$KEYSTONE_OUT" | grep -q 'object-store')
    then
        SWIFT_ENDPOINT=$(echo "$KEYSTONE_OUT" | php -r "echo array_values(array_filter(json_decode(file_get_contents('php://stdin'),true)['access']['serviceCatalog'], function(\$endpoint){return \$endpoint['type']==='object-store';}))[0]['endpoints'][0]['publicURL'];")
        SWIFT_TOKEN=$(echo "$KEYSTONE_OUT" | php -r "echo json_decode(file_get_contents('php://stdin'),true)['access']['token']['id'];")
        return 0
    else
        return -1
    fi
}

if [ "$OBJECT_STORE" == "swift" ]; then
    echo "waiting for keystone"
    until get_swift_token
    do
        sleep 2
    done

    echo "waiting for object store at $SWIFT_ENDPOINT"

    until curl -s -H "X-Auth-Token: $SWIFT_TOKEN" "$SWIFT_ENDPOINT"
    do
        sleep 2
    done

    echo "creating container"

    sleep 2

    curl curl -s -X PUT -H "X-Auth-Token: $SWIFT_TOKEN" "$SWIFT_ENDPOINT/nextcloud"

    sleep 2
fi
