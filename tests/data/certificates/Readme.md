<!--
 - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Recreating certificates

Regenerate a new certificate key together with the good (Nextcloud Security) and bad (default Org name) certificates

## Good Certificate
```
openssl req \
  -newkey rsa:2048 \
  -nodes \
  -keyout security.nextcloud.com.key \
  -x509 \
  -days 3650 \
  -out goodCertificate.crt
```
- Country Name: `DE`
- State or Province Name:`Berlin`
- Organization Name:`Nextcloud Security`
- Common Name: `security.nextcloud.com`

## Bad Certificate
```
openssl req \
  -key security.nextcloud.com.key \
  -new \
  -x509 \
  -days 3650 \
  -out badCertificate.crt
```
- Country Name: `DE`
- State or Province Name:`Berlin`
