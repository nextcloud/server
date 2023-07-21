#!/usr/bin/env bash
set -e

docker exec --user 33 apache ./occ maintenance:install --verbose --database=sqlite --database-name=nextcloud --database-host=127.0.0.1 --database-user=root --database-pass=rootpassword --admin-user admin --admin-pass password
docker exec --user 33 apache ./occ config:system:set trusted_domains 1 --value 'httpd.domain.test'

# setup user_saml
docker exec --user 33 apache ./occ app:enable user_saml --force
docker exec --user 33 apache ./occ config:app:set user_saml type --value 'environment-variable'
docker exec --user 33 apache ./occ saml:config:create
docker exec --user 33 apache ./occ saml:config:set 1 --general-uid_mapping=REMOTE_USER

# create user
docker exec -e OC_PASS=test --user 33 apache ./occ user:add 'testuser@DOMAIN.TEST' --password-from-env

# setup external storage
docker exec --user 33 apache ./occ app:enable files_external --force
docker exec --user 33 apache ./occ files_external:create smb smb smb::kerberosapache
docker exec --user 33 apache ./occ files_external:config 1 host krb.domain.test
docker exec --user 33 apache ./occ files_external:config 1 share netlogon
docker exec --user 33 apache ./occ files_external:list
