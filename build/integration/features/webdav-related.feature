Feature: webdav-related
	Background:
		Given using api version "1"

	Scenario: Unauthenticated call old dav path
		Given using old dav path
		When connecting to dav endpoint
		Then the HTTP status code should be "401"
		And there are no duplicate headers
		And The following headers should be set
			|WWW-Authenticate|Basic realm="Nextcloud", charset="UTF-8"|

	Scenario: Unauthenticated call new dav path
		Given using new dav path
		When connecting to dav endpoint
		Then the HTTP status code should be "401"
		And there are no duplicate headers
		And The following headers should be set
			|WWW-Authenticate|Basic realm="Nextcloud", charset="UTF-8"|

	Scenario: Moving a file
		Given using old dav path
		And As an "admin"
		And user "user0" exists
		And As an "user0"
		When User "user0" moves file "/welcome.txt" to "/FOLDER/welcome.txt"
		Then the HTTP status code should be "201"
		And Downloaded content when downloading file "/FOLDER/welcome.txt" with range "bytes=0-6" should be "Welcome"

	Scenario: Moving and overwriting a file old way
		Given using old dav path
		And As an "admin"
		And user "user0" exists
		And As an "user0"
		When User "user0" moves file "/welcome.txt" to "/textfile0.txt"
		Then the HTTP status code should be "204"
		And Downloaded content when downloading file "/textfile0.txt" with range "bytes=0-6" should be "Welcome"

	Scenario: Moving a file to a folder with no permissions
		Given using old dav path
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And As an "user1"
		And user "user1" created a folder "/testshare"
		And as "user1" creating a share with
		  | path | testshare |
		  | shareType | 0 |
		  | permissions | 1 |
		  | shareWith | user0 |
		And user "user0" accepts last share
		And As an "user0"
		And User "user0" moves file "/textfile0.txt" to "/testshare/textfile0.txt"
		And the HTTP status code should be "403"
		When Downloading file "/testshare/textfile0.txt"
		Then the HTTP status code should be "404"

	Scenario: Moving a file to overwrite a file in a folder with no permissions
		Given using old dav path
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And As an "user1"
		And user "user1" created a folder "/testshare"
		And as "user1" creating a share with
		  | path | testshare |
		  | shareType | 0 |
		  | permissions | 1 |
		  | shareWith | user0 |
		And user "user0" accepts last share
		And User "user1" copies file "/welcome.txt" to "/testshare/overwritethis.txt"
		And As an "user0"
		When User "user0" moves file "/textfile0.txt" to "/testshare/overwritethis.txt"
		Then the HTTP status code should be "403"
		And Downloaded content when downloading file "/testshare/overwritethis.txt" with range "bytes=0-6" should be "Welcome"

	Scenario: Copying a file
		Given using old dav path
		And As an "admin"
		And user "user0" exists
		And As an "user0"
		When User "user0" copies file "/welcome.txt" to "/FOLDER/welcome.txt"
		Then the HTTP status code should be "201"
		And Downloaded content when downloading file "/FOLDER/welcome.txt" with range "bytes=0-6" should be "Welcome"

	Scenario: Copying and overwriting a file
		Given using old dav path
		And As an "admin"
		And user "user0" exists
		And As an "user0"
		When User "user0" copies file "/welcome.txt" to "/textfile1.txt"
		Then the HTTP status code should be "204"
		And Downloaded content when downloading file "/textfile1.txt" with range "bytes=0-6" should be "Welcome"

	Scenario: Copying a file to a folder with no permissions
		Given using old dav path
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And As an "user1"
		And user "user1" created a folder "/testshare"
		And as "user1" creating a share with
		  | path | testshare |
		  | shareType | 0 |
		  | permissions | 1 |
		  | shareWith | user0 |
		And user "user0" accepts last share
		And As an "user0"
		When User "user0" copies file "/textfile0.txt" to "/testshare/textfile0.txt"
		Then the HTTP status code should be "403"
		And Downloading file "/testshare/textfile0.txt"
		And the HTTP status code should be "404"

	Scenario: Copying a file to overwrite a file into a folder with no permissions
		Given using old dav path
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And As an "user1"
		And user "user1" created a folder "/testshare"
		And as "user1" creating a share with
		  | path | testshare |
		  | shareType | 0 |
		  | permissions | 1 |
		  | shareWith | user0 |
		And user "user0" accepts last share
		And User "user1" copies file "/welcome.txt" to "/testshare/overwritethis.txt"
		And As an "user0"
		When User "user0" copies file "/textfile0.txt" to "/testshare/overwritethis.txt"
		Then the HTTP status code should be "403"
		And Downloaded content when downloading file "/testshare/overwritethis.txt" with range "bytes=0-6" should be "Welcome"

	Scenario: download a file with range
		Given using old dav path
		And As an "admin"
		When Downloading file "/welcome.txt" with range "bytes=52-78"
		Then Downloaded content should be "example file for developers"

	Scenario: Upload forbidden if quota is 0
		Given using old dav path
		And As an "admin"
		And user "user0" exists
		And user "user0" has a quota of "0"
		When User "user0" uploads file "data/textfile.txt" to "/asdf.txt"
		Then the HTTP status code should be "507"

	Scenario: Retrieving folder quota when no quota is set
		Given using old dav path
		And As an "admin"
		And user "user0" exists
		When user "user0" has unlimited quota
		Then as "user0" gets properties of folder "/" with
		  |{DAV:}quota-available-bytes|
		And the single response should contain a property "{DAV:}quota-available-bytes" with value "-3"

	Scenario: Retrieving folder quota when quota is set
		Given using old dav path
		And As an "admin"
		And user "user0" exists
		When user "user0" has a quota of "10 MB"
		Then as "user0" gets properties of folder "/" with
		  |{DAV:}quota-available-bytes|
		And the single response should contain a property "{DAV:}quota-available-bytes" with value "10485421"

	Scenario: Retrieving folder quota of shared folder with quota when no quota is set for recipient
		Given using old dav path
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
		And user "user0" accepts last share
		Then as "user0" gets properties of folder "/testquota" with
		  |{DAV:}quota-available-bytes|
		And the single response should contain a property "{DAV:}quota-available-bytes" with value "10485421"

	Scenario: Uploading a file as recipient using webdav having quota
		Given using old dav path
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
		And user "user0" accepts last share
		And As an "user0"
		When User "user0" uploads file "data/textfile.txt" to "/testquota/asdf.txt"
		Then the HTTP status code should be "201"

	Scenario: Retrieving folder quota when quota is set and a file was uploaded
		Given using old dav path
		And As an "admin"
		And user "user0" exists
		And user "user0" has a quota of "1 KB"
		And user "user0" adds a file of 93 bytes to "/prueba.txt"
		When as "user0" gets properties of folder "/" with
		  |{DAV:}quota-available-bytes|
		Then the single response should contain a property "{DAV:}quota-available-bytes" with value "592"

	Scenario: Retrieving folder quota when quota is set and a file was received
		Given using old dav path
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And user "user1" has a quota of "1 KB"
		And user "user0" adds a file of 93 bytes to "/user0.txt"
		And file "user0.txt" of user "user0" is shared with user "user1"
		And user "user1" accepts last share
		When as "user1" gets properties of folder "/" with
		  |{DAV:}quota-available-bytes|
		Then the single response should contain a property "{DAV:}quota-available-bytes" with value "685"

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
		Given using old dav path
		And As an "admin"
		When Downloading file "/welcome.txt"
		Then The following headers should be set
			|Content-Disposition|attachment; filename*=UTF-8''welcome.txt; filename="welcome.txt"|
			|Content-Security-Policy|default-src 'none';|
			|X-Content-Type-Options |nosniff|
			|X-Frame-Options|SAMEORIGIN|
			|X-Permitted-Cross-Domain-Policies|none|
			|X-Robots-Tag|noindex, nofollow|
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
		Given using new dav path
		And user "user0" exists
		And user "user0" creates a new chunking upload with id "chunking-42"
		And user "user0" uploads new chunk file "1" with "AAAAA" to id "chunking-42"
		And user "user0" uploads new chunk file "2" with "BBBBB" to id "chunking-42"
		And user "user0" uploads new chunk file "3" with "CCCCC" to id "chunking-42"
		And user "user0" moves new chunk file with id "chunking-42" to "/myChunkedFile.txt"
		When As an "user0"
		And Downloading file "/myChunkedFile.txt"
		Then Downloaded content should be "AAAAABBBBBCCCCC"

	Scenario: Upload chunked file desc with new chunking
		Given using new dav path
		And user "user0" exists
		And user "user0" creates a new chunking upload with id "chunking-42"
		And user "user0" uploads new chunk file "3" with "CCCCC" to id "chunking-42"
		And user "user0" uploads new chunk file "2" with "BBBBB" to id "chunking-42"
		And user "user0" uploads new chunk file "1" with "AAAAA" to id "chunking-42"
		And user "user0" moves new chunk file with id "chunking-42" to "/myChunkedFile.txt"
		When As an "user0"
		And Downloading file "/myChunkedFile.txt"
		Then Downloaded content should be "AAAAABBBBBCCCCC"

	Scenario: Upload chunked file random with new chunking
		Given using new dav path
		And user "user0" exists
		And user "user0" creates a new chunking upload with id "chunking-42"
		And user "user0" uploads new chunk file "2" with "BBBBB" to id "chunking-42"
		And user "user0" uploads new chunk file "3" with "CCCCC" to id "chunking-42"
		And user "user0" uploads new chunk file "1" with "AAAAA" to id "chunking-42"
		And user "user0" moves new chunk file with id "chunking-42" to "/myChunkedFile.txt"
		When As an "user0"
		And Downloading file "/myChunkedFile.txt"
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
		And user "user0" accepts last share
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
		And user "user0" accepts last share
		And User "user0" uploads file with content "copytest" to "/copytest.txt"
		When User "user0" copies file "/copytest.txt" to "/testcopypermissionsNotAllowed/copytest.txt"
		Then the HTTP status code should be "403"

	Scenario: Uploading a file as recipient with limited permissions
		Given using new dav path
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And user "user0" has a quota of "10 MB"
		And user "user1" has a quota of "10 MB"
		And As an "user1"
		And user "user1" created a folder "/testfolder"
		And as "user1" creating a share with
			| path        | testfolder |
			| shareType   | 0          |
			| permissions | 23         |
			| shareWith   | user0      |
		And user "user0" accepts last share
		And As an "user0"
		And User "user0" uploads file "data/textfile.txt" to "/testfolder/asdf.txt"
		And As an "user1"
		When User "user1" deletes file "/testfolder/asdf.txt"
		Then the HTTP status code should be "204"

	Scenario: Creating a folder
		Given using old dav path
		And user "user0" exists
		And user "user0" created a folder "/test_folder"
		When as "user0" gets properties of folder "/test_folder" with
		  |{DAV:}resourcetype|
		Then the single response should contain a property "{DAV:}resourcetype" with value "{DAV:}collection"

	Scenario: Creating a folder with special chars
		Given using old dav path
		And user "user0" exists
		And user "user0" created a folder "/test_folder:5"
		When as "user0" gets properties of folder "/test_folder:5" with
		  |{DAV:}resourcetype|
		Then the single response should contain a property "{DAV:}resourcetype" with value "{DAV:}collection"

	Scenario: Removing everything of a folder
		Given using old dav path
		And As an "admin"
		And user "user0" exists
		And As an "user0"
		And User "user0" moves file "/welcome.txt" to "/FOLDER/welcome.txt"
		And user "user0" created a folder "/FOLDER/SUBFOLDER"
		And User "user0" copies file "/textfile0.txt" to "/FOLDER/SUBFOLDER/testfile0.txt"
		When User "user0" deletes everything from folder "/FOLDER/"
		Then user "user0" should see following elements
			| /FOLDER/ |
			| /PARENT/ |
			| /PARENT/parent.txt |
			| /textfile0.txt |
			| /textfile1.txt |
			| /textfile2.txt |
			| /textfile3.txt |
			| /textfile4.txt |

	Scenario: Removing everything of a folder using new dav path
		Given using new dav path
		And As an "admin"
		And user "user0" exists
		And As an "user0"
		And User "user0" moves file "/welcome.txt" to "/FOLDER/welcome.txt"
		And user "user0" created a folder "/FOLDER/SUBFOLDER"
		And User "user0" copies file "/textfile0.txt" to "/FOLDER/SUBFOLDER/testfile0.txt"
		When User "user0" deletes everything from folder "/FOLDER/"
		Then user "user0" should see following elements
			| /FOLDER/ |
			| /PARENT/ |
			| /PARENT/parent.txt |
			| /textfile0.txt |
			| /textfile1.txt |
			| /textfile2.txt |
			| /textfile3.txt |
			| /textfile4.txt |

	Scenario: Checking file id after a move using new endpoint
		Given using new dav path
		And user "user0" exists
		And User "user0" stores id of file "/textfile0.txt"
		When User "user0" moves file "/textfile0.txt" to "/FOLDER/textfile0.txt"
		Then User "user0" checks id of file "/FOLDER/textfile0.txt"

	Scenario: Checking file id after a move overwrite using new chunking endpoint
		Given using new dav path
		And user "user0" exists
		And User "user0" copies file "/textfile0.txt" to "/existingFile.txt"
		And User "user0" stores id of file "/existingFile.txt"
		And user "user0" creates a new chunking upload with id "chunking-42"
		And user "user0" uploads new chunk file "1" with "AAAAA" to id "chunking-42"
		And user "user0" uploads new chunk file "2" with "BBBBB" to id "chunking-42"
		And user "user0" uploads new chunk file "3" with "CCCCC" to id "chunking-42"
		When user "user0" moves new chunk file with id "chunking-42" to "/existingFile.txt"
		Then User "user0" checks id of file "/existingFile.txt"

	Scenario: Renaming a folder to a backslash encoded should return an error using old endpoint
		Given using old dav path
		And user "user0" exists
		And user "user0" created a folder "/testshare"
		When User "user0" moves folder "/testshare" to "/%5C"
		Then the HTTP status code should be "400"

	Scenario: Renaming a folder beginning with a backslash encoded should return an error using old endpoint
		Given using old dav path
		And user "user0" exists
		And user "user0" created a folder "/testshare"
		When User "user0" moves folder "/testshare" to "/%5Ctestshare"
		Then the HTTP status code should be "400"

	Scenario: Renaming a folder including a backslash encoded should return an error using old endpoint
		Given using old dav path
		And user "user0" exists
		And user "user0" created a folder "/testshare"
		When User "user0" moves folder "/testshare" to "/hola%5Chola"
		Then the HTTP status code should be "400"

	Scenario: Renaming a folder to a backslash encoded should return an error using new endpoint
		Given using new dav path
		And user "user0" exists
		And user "user0" created a folder "/testshare"
		When User "user0" moves folder "/testshare" to "/%5C"
		Then the HTTP status code should be "400"

	Scenario: Renaming a folder beginning with a backslash encoded should return an error using new endpoint
		Given using new dav path
		And user "user0" exists
		And user "user0" created a folder "/testshare"
		When User "user0" moves folder "/testshare" to "/%5Ctestshare"
		Then the HTTP status code should be "400"

	Scenario: Renaming a folder including a backslash encoded should return an error using new endpoint
		Given using new dav path
		And user "user0" exists
		And user "user0" created a folder "/testshare"
		When User "user0" moves folder "/testshare" to "/hola%5Chola"
		Then the HTTP status code should be "400"

	Scenario: Upload file via new chunking endpoint with wrong size header
		Given using new dav path
		And user "user0" exists
		And user "user0" creates a new chunking upload with id "chunking-42"
		And user "user0" uploads new chunk file "1" with "AAAAA" to id "chunking-42"
		And user "user0" uploads new chunk file "2" with "BBBBB" to id "chunking-42"
		And user "user0" uploads new chunk file "3" with "CCCCC" to id "chunking-42"
		When user "user0" moves new chunk file with id "chunking-42" to "/myChunkedFile.txt" with size 5
		Then the HTTP status code should be "400"

	Scenario: Upload file via new chunking endpoint with correct size header
		Given using new dav path
		And user "user0" exists
		And user "user0" creates a new chunking upload with id "chunking-42"
		And user "user0" uploads new chunk file "1" with "AAAAA" to id "chunking-42"
		And user "user0" uploads new chunk file "2" with "BBBBB" to id "chunking-42"
		And user "user0" uploads new chunk file "3" with "CCCCC" to id "chunking-42"
		When user "user0" moves new chunk file with id "chunking-42" to "/myChunkedFile.txt" with size 15
		Then the HTTP status code should be "201"

	Scenario: Upload bulked files
		Given user "user0" exists
		And user "user0" uploads bulked files "A.txt" with "AAAAA" and "B.txt" with "BBBBB" and "C.txt" with "CCCCC"
		When As an "user0"
		Then Downloading file "/A.txt"
		And Downloaded content should be "AAAAA"
		And File "/A.txt" should have prop "d:getlastmodified" equal to "Fri, 18 Mar 2005 01:58:31 GMT"
		And Downloading file "/B.txt"
		And Downloaded content should be "BBBBB"
		And File "/B.txt" should have prop "d:getlastmodified" equal to "Sat, 02 Jun 2040 03:57:02 GMT"
		And Downloading file "/C.txt"
		And Downloaded content should be "CCCCC"
		And File "/C.txt" should have prop "d:getlastmodified" equal to "Sun, 18 Aug 2075 05:55:33 GMT"

	Scenario: Creating a folder with invalid characters
		Given using new dav path
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And As an "user1"
		And user "user1" created a folder "/testshare	"
		Then the HTTP status code should be "400"

	@s3-multipart
	Scenario: Upload chunked file asc with new chunking v2
		Given using new dav path
		And user "user0" exists
		And user "user0" creates a file locally with "3" x 5 MB chunks
		And user "user0" creates a new chunking v2 upload with id "chunking-42" and destination "/myChunkedFile1.txt"
		And user "user0" uploads new chunk v2 file "1" to id "chunking-42"
		And user "user0" uploads new chunk v2 file "2" to id "chunking-42"
		And user "user0" uploads new chunk v2 file "3" to id "chunking-42"
		And user "user0" moves new chunk v2 file with id "chunking-42"
		Then the S3 multipart upload was successful with status "201"
		When As an "user0"
		And Downloading file "/myChunkedFile1.txt"
		Then Downloaded content should be the created file

	@s3-multipart
	Scenario: Upload chunked file desc with new chunking v2
		Given using new dav path
		And user "user0" exists
		And user "user0" creates a file locally with "3" x 5 MB chunks
		And user "user0" creates a new chunking v2 upload with id "chunking-42" and destination "/myChunkedFile.txt"
		And user "user0" uploads new chunk v2 file "3" to id "chunking-42"
		And user "user0" uploads new chunk v2 file "2" to id "chunking-42"
		And user "user0" uploads new chunk v2 file "1" to id "chunking-42"
		And user "user0" moves new chunk v2 file with id "chunking-42"
		Then the S3 multipart upload was successful with status "201"
		When As an "user0"
		And Downloading file "/myChunkedFile.txt"
		Then Downloaded content should be the created file

	@s3-multipart
	Scenario: Upload chunked file with random chunk sizes
		Given using new dav path
		And user "user0" exists
		And user "user0" creates a new chunking v2 upload with id "chunking-random" and destination "/myChunkedFile.txt"
		And user user0 creates the chunk 1 with a size of 5 MB
		And user user0 creates the chunk 2 with a size of 7 MB
		And user user0 creates the chunk 3 with a size of 9 MB
		And user user0 creates the chunk 4 with a size of 1 MB
		And user "user0" uploads new chunk v2 file "1" to id "chunking-random"
		And user "user0" uploads new chunk v2 file "3" to id "chunking-random"
		And user "user0" uploads new chunk v2 file "2" to id "chunking-random"
		And user "user0" uploads new chunk v2 file "4" to id "chunking-random"
		And user "user0" moves new chunk v2 file with id "chunking-random"
		Then the S3 multipart upload was successful with status "201"
		When As an "user0"
		And Downloading file "/myChunkedFile.txt"
		Then Downloaded content should be the created file

	@s3-multipart
	Scenario: Upload chunked file with too low chunk sizes
		Given using new dav path
		And user "user0" exists
		And user "user0" creates a new chunking v2 upload with id "chunking-random" and destination "/myChunkedFile.txt"
		And user user0 creates the chunk 1 with a size of 5 MB
		And user user0 creates the chunk 2 with a size of 2 MB
		And user user0 creates the chunk 3 with a size of 5 MB
		And user user0 creates the chunk 4 with a size of 1 MB
		And user "user0" uploads new chunk v2 file "1" to id "chunking-random"
		And user "user0" uploads new chunk v2 file "3" to id "chunking-random"
		And user "user0" uploads new chunk v2 file "2" to id "chunking-random"
		And user "user0" uploads new chunk v2 file "4" to id "chunking-random"
		And user "user0" moves new chunk v2 file with id "chunking-random"
    Then the upload should fail on object storage

	@s3-multipart
	Scenario: Upload chunked file with special characters with new chunking v2
		Given using new dav path
		And user "user0" exists
		And user "user0" creates a file locally with "3" x 5 MB chunks
		And user "user0" creates a new chunking v2 upload with id "chunking-42" and destination "/äöü.txt"
		And user "user0" uploads new chunk v2 file "1" to id "chunking-42"
		And user "user0" uploads new chunk v2 file "2" to id "chunking-42"
		And user "user0" uploads new chunk v2 file "3" to id "chunking-42"
		And user "user0" moves new chunk v2 file with id "chunking-42"
		Then the S3 multipart upload was successful with status "201"
		When As an "user0"
		And Downloading file "/äöü.txt"
		Then Downloaded content should be the created file

	@s3-multipart
	Scenario: Upload chunked file with special characters in path with new chunking v2
		Given using new dav path
		And user "user0" exists
		And User "user0" created a folder "üäöé"
		And user "user0" creates a file locally with "3" x 5 MB chunks
		And user "user0" creates a new chunking v2 upload with id "chunking-42" and destination "/üäöé/äöü.txt"
		And user "user0" uploads new chunk v2 file "1" to id "chunking-42"
		And user "user0" uploads new chunk v2 file "2" to id "chunking-42"
		And user "user0" uploads new chunk v2 file "3" to id "chunking-42"
		And user "user0" moves new chunk v2 file with id "chunking-42"
		Then the S3 multipart upload was successful with status "201"
		When As an "user0"
		And Downloading file "/üäöé/äöü.txt"
		Then Downloaded content should be the created file
