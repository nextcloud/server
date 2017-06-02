#!/bin/bash

if [ ! -e "/tmp/project.info" ]; then
    project=$(gcloud info --format json | jq '. | .config.project' | tr -d '"')
    echo "project=$project" > /tmp/project.info
fi

if [ ! -e "/tmp/token.info" ]; then
    accessToken=$(gcloud auth application-default print-access-token)
    echo "token=$accessToken" > /tmp/token.info
fi

source /tmp/project.info
source /tmp/token.info

keyRing='administration'
keyName='nextcloud'

body="{'ciphertext':'$1'}"
api="https://cloudkms.googleapis.com/v1beta1/projects/$project/locations/global/keyRings/$keyRing/cryptoKeys/$keyName:decrypt"
result=$(curl -H "Authorization: Bearer $accessToken" -H 'Content-Type: application/json' -X POST -d $body $api -s)

error=$(echo $result | jq '. | .error.status' | tr -d '"')
if [ "$error" == "UNAUTHENTICATED" ]; then
    accessToken=$(gcloud auth application-default print-access-token)
    echo "token=$accessToken" > /tmp/token.info
    result=$(curl -H "Authorization: Bearer $accessToken" -H 'Content-Type: application/json' -X POST -d $body $api -s)
fi

result=$(echo $result | jq '. | .plaintext' | tr -d '"')

if [ "$result" == "" ]; then
    echo "error"
else
    decrypted_value=$(echo $result | base64 --decode)
    echo "$decrypted_value"
fi

