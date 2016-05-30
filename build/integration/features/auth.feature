Feature: auth

	Background:
		Given user "user0" exists
		Given a new client token is used


	# FILES APP

	Scenario: access files app anonymously
		When requesting "/index.php/apps/files" with "GET"
		Then the HTTP status code should be "401"

	Scenario: access files app with basic auth
		When requesting "/index.php/apps/files" with "GET" using basic auth
		Then the HTTP status code should be "200"

	Scenario: access files app with basic token auth
		When requesting "/index.php/apps/files" with "GET" using basic token auth
		Then the HTTP status code should be "200"

	Scenario: access files app with a client token
		When requesting "/index.php/apps/files" with "GET" using a client token
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

	Scenario: using WebDAV with token auth
		When requesting "/remote.php/webdav" with "PROPFIND" using basic token auth
		Then the HTTP status code should be "207"

	# DAV token auth is not possible yet
	#Scenario: using WebDAV with a client token
	#	When requesting "/remote.php/webdav" with "PROPFIND" using a client token
	#	Then the HTTP status code should be "207"

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
		When requesting "/ocs/v1.php/apps/files_sharing/api/v1/remote_shares" with "GET" using basic token auth
		Then the OCS status code should be "100"

	Scenario: using OCS with client token
		When requesting "/ocs/v1.php/apps/files_sharing/api/v1/remote_shares" with "GET" using a client token
		Then the OCS status code should be "100"

	Scenario: using OCS with browser session
		Given a new browser session is started
		When requesting "/ocs/v1.php/apps/files_sharing/api/v1/remote_shares" with "GET" using browser session
		Then the OCS status code should be "100"