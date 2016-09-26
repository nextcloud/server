Feature: webdav-related
	Background:
		Given using api version "1"

	Scenario: moving a file old way
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		When User "user0" moves file "/textfile0.txt" to "/FOLDER/textfile0.txt"
		Then the HTTP status code should be "201"

	Scenario: download a file with range
		Given using dav path "remote.php/webdav"
		And As an "admin"
		When Downloading file "/welcome.txt" with range "bytes=52-78"
		Then Downloaded content should be "example file for developers"

	Scenario: Upload forbidden if quota is 0
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		And user "user0" has a quota of "0"
		When User "user0" uploads file "data/textfile.txt" to "/asdf.txt"
		Then the HTTP status code should be "507"

	Scenario: Retrieving folder quota when no quota is set
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		When user "user0" has unlimited quota
		Then as "user0" gets properties of folder "/" with
		  |{DAV:}quota-available-bytes|
		And the single response should contain a property "{DAV:}quota-available-bytes" with value "-3"

	Scenario: Retrieving folder quota when quota is set
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		When user "user0" has a quota of "10 MB"
		Then as "user0" gets properties of folder "/" with
		  |{DAV:}quota-available-bytes|
		And the single response should contain a property "{DAV:}quota-available-bytes" with value "10485421"

	Scenario: Retrieving folder quota of shared folder with quota when no quota is set for recipient
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And user "user0" has unlimited quota
		And user "user1" has a quota of "10 MB"
		And As an "user1"
		And user "user1" created a folder "/testquota"
		And as "user1" creating a share with
		  | path | testquota |
		  | shareType | 0 |
		  | permissions | 31 |
		  | shareWith | user0 |
		Then as "user0" gets properties of folder "/testquota" with
		  |{DAV:}quota-available-bytes|
		And the single response should contain a property "{DAV:}quota-available-bytes" with value "10485421"

	Scenario: Uploading a file as recipient using webdav having quota
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And user "user0" has a quota of "10 MB"
		And user "user1" has a quota of "10 MB"
		And As an "user1"
		And user "user1" created a folder "/testquota"
		And as "user1" creating a share with
		  | path | testquota |
		  | shareType | 0 |
		  | permissions | 31 |
		  | shareWith | user0 |
		And As an "user0"
		When User "user0" uploads file "data/textfile.txt" to "/testquota/asdf.txt"
		Then the HTTP status code should be "201"

	Scenario: Retrieving folder quota when quota is set and a file was uploaded
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		And user "user0" has a quota of "1 KB"
		And user "user0" adds a file of 93 bytes to "/prueba.txt"
		When as "user0" gets properties of folder "/" with
		  |{DAV:}quota-available-bytes|
		Then the single response should contain a property "{DAV:}quota-available-bytes" with value "600"

	Scenario: Retrieving folder quota when quota is set and a file was recieved
		Given using dav path "remote.php/webdav"
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And user "user1" has a quota of "1 KB"
		And user "user0" adds a file of 93 bytes to "/user0.txt"
		And file "user0.txt" of user "user0" is shared with user "user1"
		When as "user1" gets properties of folder "/" with
		  |{DAV:}quota-available-bytes|
		Then the single response should contain a property "{DAV:}quota-available-bytes" with value "693"

	Scenario: download a public shared file with range
		Given user "user0" exists
		And As an "user0"
		When creating a share with
			| path | welcome.txt |
			| shareType | 3 |
		And Downloading last public shared file with range "bytes=52-78"
		Then Downloaded content should be "example file for developers"

	Scenario: download a public shared file inside a folder with range
		Given user "user0" exists
		And As an "user0"
		When creating a share with
			| path | PARENT |
			| shareType | 3 |
		And Downloading last public shared file inside a folder "/parent.txt" with range "bytes=1-8"
		Then Downloaded content should be "extcloud"

	Scenario: Downloading a file on the old endpoint should serve security headers
		Given using dav path "remote.php/webdav"
		And As an "admin"
		When Downloading file "/welcome.txt"
		Then The following headers should be set
			|Content-Disposition|attachment; filename*=UTF-8''welcome.txt; filename="welcome.txt"|
			|Content-Security-Policy|default-src 'none';|
			|X-Content-Type-Options |nosniff|
			|X-Download-Options|noopen|
			|X-Frame-Options|Sameorigin|
			|X-Permitted-Cross-Domain-Policies|none|
			|X-Robots-Tag|none|
			|X-XSS-Protection|1; mode=block|
		And Downloaded content should start with "Welcome to your Nextcloud account!"

	Scenario: Doing a GET with a web login should work without CSRF token on the old backend
		Given Logging in using web as "admin"
		When Sending a "GET" to "/remote.php/webdav/welcome.txt" without requesttoken
		Then Downloaded content should start with "Welcome to your Nextcloud account!"
		Then the HTTP status code should be "200"

	Scenario: Doing a GET with a web login should work with CSRF token on the old backend
		Given Logging in using web as "admin"
		When Sending a "GET" to "/remote.php/webdav/welcome.txt" with requesttoken
		Then Downloaded content should start with "Welcome to your Nextcloud account!"
		Then the HTTP status code should be "200"

	Scenario: Doing a PROPFIND with a web login should not work without CSRF token on the old backend
		Given Logging in using web as "admin"
		When Sending a "PROPFIND" to "/remote.php/webdav/welcome.txt" without requesttoken
		Then the HTTP status code should be "401"

	Scenario: Doing a PROPFIND with a web login should work with CSRF token on the old backend
		Given Logging in using web as "admin"
		When Sending a "PROPFIND" to "/remote.php/webdav/welcome.txt" with requesttoken
		Then the HTTP status code should be "207"

	Scenario: Upload chunked file asc
		Given user "user0" exists
		And user "user0" uploads chunk file "1" of "3" with "AAAAA" to "/myChunkedFile.txt"
		And user "user0" uploads chunk file "2" of "3" with "BBBBB" to "/myChunkedFile.txt"
		And user "user0" uploads chunk file "3" of "3" with "CCCCC" to "/myChunkedFile.txt"
		When As an "user0"
		And Downloading file "/myChunkedFile.txt"
		Then Downloaded content should be "AAAAABBBBBCCCCC"

	Scenario: Upload chunked file desc
		Given user "user0" exists
		And user "user0" uploads chunk file "3" of "3" with "CCCCC" to "/myChunkedFile.txt"
		And user "user0" uploads chunk file "2" of "3" with "BBBBB" to "/myChunkedFile.txt"
		And user "user0" uploads chunk file "1" of "3" with "AAAAA" to "/myChunkedFile.txt"
		When As an "user0"
		And Downloading file "/myChunkedFile.txt"
		Then Downloaded content should be "AAAAABBBBBCCCCC"

	Scenario: Upload chunked file random
		Given user "user0" exists
		And user "user0" uploads chunk file "2" of "3" with "BBBBB" to "/myChunkedFile.txt"
		And user "user0" uploads chunk file "3" of "3" with "CCCCC" to "/myChunkedFile.txt"
		And user "user0" uploads chunk file "1" of "3" with "AAAAA" to "/myChunkedFile.txt"
		When As an "user0"
		And Downloading file "/myChunkedFile.txt"
		Then Downloaded content should be "AAAAABBBBBCCCCC"

	Scenario: A file that is not shared does not have a share-types property
		Given user "user0" exists
		And user "user0" created a folder "/test"
		When as "user0" gets properties of folder "/test" with
			|{http://owncloud.org/ns}share-types|
		Then the response should contain an empty property "{http://owncloud.org/ns}share-types"

	Scenario: A file that is shared to a user has a share-types property
		Given user "user0" exists
		And user "user1" exists
		And user "user0" created a folder "/test"
		And as "user0" creating a share with
			| path | test |
			| shareType | 0 |
			| permissions | 31 |
			| shareWith | user1 |
		When as "user0" gets properties of folder "/test" with
			|{http://owncloud.org/ns}share-types|
		Then the response should contain a share-types property with
			| 0 |

	Scenario: A file that is shared to a group has a share-types property
		Given user "user0" exists
		And group "group1" exists
		And user "user0" created a folder "/test"
		And as "user0" creating a share with
			| path | test |
			| shareType | 1 |
			| permissions | 31 |
			| shareWith | group1 |
		When as "user0" gets properties of folder "/test" with
			|{http://owncloud.org/ns}share-types|
		Then the response should contain a share-types property with
			| 1 |

	Scenario: A file that is shared by link has a share-types property
		Given user "user0" exists
		And user "user0" created a folder "/test"
		And as "user0" creating a share with
			| path | test |
			| shareType | 3 |
			| permissions | 31 |
		When as "user0" gets properties of folder "/test" with
			|{http://owncloud.org/ns}share-types|
		Then the response should contain a share-types property with
			| 3 |

	Scenario: A file that is shared by user,group and link has a share-types property
		Given user "user0" exists
		And user "user1" exists
		And group "group2" exists
		And user "user0" created a folder "/test"
		And as "user0" creating a share with
			| path        | test  |
			| shareType   | 0     |
			| permissions | 31    |
			| shareWith   | user1 |
		And as "user0" creating a share with
			| path        | test  |
			| shareType   | 1     |
			| permissions | 31    |
			| shareWith   | group2 |
		And as "user0" creating a share with
			| path        | test  |
			| shareType   | 3     |
			| permissions | 31    |
		When as "user0" gets properties of folder "/test" with
			|{http://owncloud.org/ns}share-types|
		Then the response should contain a share-types property with
			| 0 |
			| 1 |
			| 3 |

	Scenario: Upload chunked file asc with new chunking
		Given using dav path "remote.php/dav"
		And user "user0" exists
		And user "user0" creates a new chunking upload with id "chunking-42"
		And user "user0" uploads new chunk file "1" with "AAAAA" to id "chunking-42"
		And user "user0" uploads new chunk file "2" with "BBBBB" to id "chunking-42"
		And user "user0" uploads new chunk file "3" with "CCCCC" to id "chunking-42"
		And user "user0" moves new chunk file with id "chunking-42" to "/myChunkedFile.txt"
		When As an "user0"
		And Downloading file "/files/user0/myChunkedFile.txt"
		Then Downloaded content should be "AAAAABBBBBCCCCC"

	Scenario: Upload chunked file desc with new chunking
		Given using dav path "remote.php/dav"
		And user "user0" exists
		And user "user0" creates a new chunking upload with id "chunking-42"
		And user "user0" uploads new chunk file "3" with "CCCCC" to id "chunking-42"
		And user "user0" uploads new chunk file "2" with "BBBBB" to id "chunking-42"
		And user "user0" uploads new chunk file "1" with "AAAAA" to id "chunking-42"
		And user "user0" moves new chunk file with id "chunking-42" to "/myChunkedFile.txt"
		When As an "user0"
		And Downloading file "/files/user0/myChunkedFile.txt"
		Then Downloaded content should be "AAAAABBBBBCCCCC"

	Scenario: Upload chunked file random with new chunking
		Given using dav path "remote.php/dav"
		And user "user0" exists
		And user "user0" creates a new chunking upload with id "chunking-42"
		And user "user0" uploads new chunk file "2" with "BBBBB" to id "chunking-42"
		And user "user0" uploads new chunk file "3" with "CCCCC" to id "chunking-42"
		And user "user0" uploads new chunk file "1" with "AAAAA" to id "chunking-42"
		And user "user0" moves new chunk file with id "chunking-42" to "/myChunkedFile.txt"
		When As an "user0"
		And Downloading file "/files/user0/myChunkedFile.txt"
		Then Downloaded content should be "AAAAABBBBBCCCCC"

	Scenario: A disabled user cannot use webdav
		Given user "userToBeDisabled" exists
		And As an "admin"
		And assure user "userToBeDisabled" is disabled
		When Downloading file "/welcome.txt" as "userToBeDisabled"
		Then the HTTP status code should be "503"

	Scenario: Copying files into a folder with edit permissions
		Given using dav path "remote.php/webdav"
		And user "user0" exists
		And user "user1" exists
		And As an "user1"
		And user "user1" created a folder "/testcopypermissionsAllowed"
		And as "user1" creating a share with
			| path | testcopypermissionsAllowed |
			| shareType | 0 |
			| permissions | 31 |
			| shareWith | user0 |
		And User "user0" uploads file with content "copytest" to "/copytest.txt"
		When User "user0" copies file "/copytest.txt" to "/testcopypermissionsAllowed/copytest.txt"
		Then the HTTP status code should be "201"

	Scenario: Copying files into a folder without edit permissions
		Given using dav path "remote.php/webdav"
		And user "user0" exists
		And user "user1" exists
		And As an "user1"
		And user "user1" created a folder "/testcopypermissionsNotAllowed"
		And as "user1" creating a share with
			| path | testcopypermissionsNotAllowed |
			| shareType | 0 |
			| permissions | 1 |
			| shareWith | user0 |
		And User "user0" uploads file with content "copytest" to "/copytest.txt"
		When User "user0" copies file "/copytest.txt" to "/testcopypermissionsNotAllowed/copytest.txt"
		Then the HTTP status code should be "403"
