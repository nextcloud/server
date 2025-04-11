# SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
# SPDX-FileCopyrightText: 2016 ownCloud, Inc.
# SPDX-License-Identifier: AGPL-3.0-only
Feature: auth

	Background:
		Given user "user0" exists
		Given a new restricted client token is added
		Given a new unrestricted client token is added
		Given the cookie jar is reset

	# FILES APP
	Scenario: access files app anonymously
		When requesting "/index.php/apps/files" with "GET"
		Then the HTTP status code should be "401"

	Scenario: access files app with basic auth
		When requesting "/index.php/apps/files" with "GET" using basic auth
		Then the HTTP status code should be "200"

	Scenario: access files app with unrestricted basic token auth
		When requesting "/index.php/apps/files" with "GET" using unrestricted basic token auth
		Then the HTTP status code should be "200"
		Then requesting "/remote.php/files/welcome.txt" with "GET" using browser session
		Then the HTTP status code should be "200"

	Scenario: access files app with restricted basic token auth
		When requesting "/index.php/apps/files" with "GET" using restricted basic token auth
		Then the HTTP status code should be "200"
		Then requesting "/remote.php/files/welcome.txt" with "GET" using browser session
		Then the HTTP status code should be "404"

	Scenario: access files app with an unrestricted client token
		When requesting "/index.php/apps/files" with "GET" using an unrestricted client token
		Then the HTTP status code should be "200"

	Scenario: access files app with browser session
		Given a new browser session is started
		When requesting "/index.php/apps/files" with "GET" using browser session
		Then the HTTP status code should be "200"

	# WebDAV
	Scenario: using WebDAV anonymously
		When requesting "/remote.php/webdav" with "PROPFIND"
		Then the HTTP status code should be "401"

	Scenario: using WebDAV with basic auth
		When requesting "/remote.php/webdav" with "PROPFIND" using basic auth
		Then the HTTP status code should be "207"

	Scenario: using WebDAV with unrestricted basic token auth
		When requesting "/remote.php/webdav" with "PROPFIND" using unrestricted basic token auth
		Then the HTTP status code should be "207"

	Scenario: using WebDAV with restricted basic token auth
		When requesting "/remote.php/webdav" with "PROPFIND" using restricted basic token auth
		Then the HTTP status code should be "207"

	Scenario: using old WebDAV endpoint with unrestricted client token
		When requesting "/remote.php/webdav" with "PROPFIND" using an unrestricted client token
		Then the HTTP status code should be "207"

	Scenario: using new WebDAV endpoint with unrestricted client token
		When requesting "/remote.php/dav/" with "PROPFIND" using an unrestricted client token
		Then the HTTP status code should be "207"

	Scenario: using WebDAV with browser session
		Given a new browser session is started
		When requesting "/remote.php/webdav" with "PROPFIND" using browser session
		Then the HTTP status code should be "207"

	# OCS
	Scenario: using OCS anonymously
		When requesting "/ocs/v1.php/apps/files_sharing/api/v1/remote_shares" with "GET"
		Then the OCS status code should be "997"

	Scenario: using OCS with basic auth
		When requesting "/ocs/v1.php/apps/files_sharing/api/v1/remote_shares" with "GET" using basic auth
		Then the OCS status code should be "100"

	Scenario: using OCS with token auth
		When requesting "/ocs/v1.php/apps/files_sharing/api/v1/remote_shares" with "GET" using unrestricted basic token auth
		Then the OCS status code should be "100"

	Scenario: using OCS with an unrestricted client token
		When requesting "/ocs/v1.php/apps/files_sharing/api/v1/remote_shares" with "GET" using an unrestricted client token
		Then the OCS status code should be "100"

	Scenario: using OCS with browser session
		Given a new browser session is started
		When requesting "/ocs/v1.php/apps/files_sharing/api/v1/remote_shares" with "GET" using browser session
		Then the OCS status code should be "100"

	# REMEMBER ME
	Scenario: remember login
		Given a new remembered browser session is started
		When the session cookie expires
		And requesting "/index.php/apps/files" with "GET" using browser session
		Then the HTTP status code should be "200"

	# AUTH TOKENS
	Scenario: Creating an auth token with regular auth token should not work
		When requesting "/index.php/apps/files" with "GET" using restricted basic token auth
		Then the HTTP status code should be "200"
		When the CSRF token is extracted from the previous response
		When a new unrestricted client token is added using restricted basic token auth
		Then the HTTP status code should be "503"

	Scenario: Creating a restricted auth token with regular login should work
		When a new restricted client token is added
		Then the HTTP status code should be "200"

	Scenario: Creating an unrestricted auth token with regular login should work
		When a new unrestricted client token is added
		Then the HTTP status code should be "200"

