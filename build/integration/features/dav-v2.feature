Feature: dav-v2
	Background:
		Given using api version "1"

	Scenario: moving a file new endpoint way
		Given using dav path "remote.php/dav"
		And As an "admin"
		And user "user0" exists
		When User "user0" moves file "/files/user0/textfile0.txt" to "/files/user0/FOLDER/textfile0.txt"
		Then the HTTP status code should be "201"

	Scenario: download a file with range using new endpoint
		Given using dav path "remote.php/dav"
		And As an "admin"
		When Downloading file "/files/user0/welcome.txt" with range "bytes=51-77"
		Then Downloaded content should be "example file for developers"

	Scenario: Downloading a file on the new endpoint should serve security headers
		Given using dav path "remote.php/dav/files/admin/"
		And As an "admin"
		When Downloading file "welcome.txt"
		Then The following headers should be set
			|Content-Disposition|attachment|
			|Content-Security-Policy|default-src 'none';|
			|X-Content-Type-Options |nosniff|
			|X-Download-Options|noopen|
			|X-Frame-Options|Sameorigin|
			|X-Permitted-Cross-Domain-Policies|none|
			|X-Robots-Tag|none|
			|X-XSS-Protection|1; mode=block|
		And Downloaded content should start with "Welcome to your ownCloud account!"

	Scenario: Doing a GET with a web login should work without CSRF token on the new backend
		Given Logging in using web as "admin"
		When Sending a "GET" to "/remote.php/dav/files/admin/welcome.txt" without requesttoken
		Then Downloaded content should start with "Welcome to your ownCloud account!"
		Then the HTTP status code should be "200"

	Scenario: Doing a GET with a web login should work with CSRF token on the new backend
		Given Logging in using web as "admin"
		When Sending a "GET" to "/remote.php/dav/files/admin/welcome.txt" with requesttoken
		Then Downloaded content should start with "Welcome to your ownCloud account!"
		Then the HTTP status code should be "200"

	Scenario: Doing a PROPFIND with a web login should not work without CSRF token on the new backend
		Given Logging in using web as "admin"
		When Sending a "PROPFIND" to "/remote.php/dav/files/admin/welcome.txt" without requesttoken
		Then the HTTP status code should be "401"

	Scenario: Doing a PROPFIND with a web login should work with CSRF token on the new backend
		Given Logging in using web as "admin"
		When Sending a "PROPFIND" to "/remote.php/dav/files/admin/welcome.txt" with requesttoken
		Then the HTTP status code should be "207"
