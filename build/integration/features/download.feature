Feature: download

	Scenario: downloading 2 small files returns a zip32
		Given using new dav path
		And user "user0" exists
		And User "user0" copies file "/welcome.txt" to "/welcome2.txt"
		When user "user0" downloads zip file for entries '"welcome.txt","welcome2.txt"' in folder "/"
		Then the downloaded zip file is a zip32 file
		And the downloaded zip file contains a file named "welcome.txt" with the contents of "/welcome.txt" from "user0" data
		And the downloaded zip file contains a file named "welcome2.txt" with the contents of "/welcome2.txt" from "user0" data

	Scenario: downloading a small file and a directory returns a zip32
		Given using new dav path
		And user "user0" exists
		And user "user0" created a folder "/emptySubFolder"
		When user "user0" downloads zip file for entries '"welcome.txt","emptySubFolder"' in folder "/"
		Then the downloaded zip file is a zip32 file
		And the downloaded zip file contains a file named "welcome.txt" with the contents of "/welcome.txt" from "user0" data
		And the downloaded zip file contains a folder named "emptySubFolder/"

	Scenario: downloading a small file and 2 nested directories returns a zip32
		Given using new dav path
		And user "user0" exists
		And user "user0" created a folder "/subFolder"
		And user "user0" created a folder "/subFolder/emptySubSubFolder"
		When user "user0" downloads zip file for entries '"welcome.txt","subFolder"' in folder "/"
		Then the downloaded zip file is a zip32 file
		And the downloaded zip file contains a file named "welcome.txt" with the contents of "/welcome.txt" from "user0" data
		And the downloaded zip file contains a folder named "subFolder/"
		And the downloaded zip file contains a folder named "subFolder/emptySubSubFolder/"

	Scenario: downloading dir with 2 small files returns a zip32
		Given using new dav path
		And user "user0" exists
		And user "user0" created a folder "/sparseFolder"
		And User "user0" copies file "/welcome.txt" to "/sparseFolder/welcome.txt"
		And User "user0" copies file "/welcome.txt" to "/sparseFolder/welcome2.txt"
		When user "user0" downloads zip file for entries '"sparseFolder"' in folder "/"
		Then the downloaded zip file is a zip32 file
		And the downloaded zip file contains a folder named "sparseFolder/"
		And the downloaded zip file contains a file named "sparseFolder/welcome.txt" with the contents of "/sparseFolder/welcome.txt" from "user0" data
		And the downloaded zip file contains a file named "sparseFolder/welcome2.txt" with the contents of "/sparseFolder/welcome2.txt" from "user0" data

	Scenario: downloading dir with a small file and a directory returns a zip32
		Given using new dav path
		And user "user0" exists
		And user "user0" created a folder "/sparseFolder"
		And User "user0" copies file "/welcome.txt" to "/sparseFolder/welcome.txt"
		And user "user0" created a folder "/sparseFolder/emptySubFolder"
		When user "user0" downloads zip file for entries '"sparseFolder"' in folder "/"
		Then the downloaded zip file is a zip32 file
		And the downloaded zip file contains a folder named "sparseFolder/"
		And the downloaded zip file contains a file named "sparseFolder/welcome.txt" with the contents of "/sparseFolder/welcome.txt" from "user0" data
		And the downloaded zip file contains a folder named "sparseFolder/emptySubFolder/"

	Scenario: downloading dir with a small file and 2 nested directories returns a zip32
		Given using new dav path
		And user "user0" exists
		And user "user0" created a folder "/sparseFolder"
		And User "user0" copies file "/welcome.txt" to "/sparseFolder/welcome.txt"
		And user "user0" created a folder "/sparseFolder/subFolder"
		And user "user0" created a folder "/sparseFolder/subFolder/emptySubSubFolder"
		When user "user0" downloads zip file for entries '"sparseFolder"' in folder "/"
		Then the downloaded zip file is a zip32 file
		And the downloaded zip file contains a folder named "sparseFolder/"
		And the downloaded zip file contains a file named "sparseFolder/welcome.txt" with the contents of "/sparseFolder/welcome.txt" from "user0" data
		And the downloaded zip file contains a folder named "sparseFolder/subFolder/"
		And the downloaded zip file contains a folder named "sparseFolder/subFolder/emptySubSubFolder/"

	Scenario: downloading (from folder) 2 small files returns a zip32
		Given using new dav path
		And user "user0" exists
		And user "user0" created a folder "/baseFolder"
		And User "user0" copies file "/welcome.txt" to "/baseFolder/welcome.txt"
		And User "user0" copies file "/welcome.txt" to "/baseFolder/welcome2.txt"
		When user "user0" downloads zip file for entries '"welcome.txt","welcome2.txt"' in folder "/baseFolder/"
		Then the downloaded zip file is a zip32 file
		And the downloaded zip file contains a file named "welcome.txt" with the contents of "/baseFolder/welcome.txt" from "user0" data
		And the downloaded zip file contains a file named "welcome2.txt" with the contents of "/baseFolder/welcome2.txt" from "user0" data

	Scenario: downloading (from folder) a small file and a directory returns a zip32
		Given using new dav path
		And user "user0" exists
		And user "user0" created a folder "/baseFolder"
		And User "user0" copies file "/welcome.txt" to "/baseFolder/welcome.txt"
		And user "user0" created a folder "/baseFolder/emptySubFolder"
		When user "user0" downloads zip file for entries '"welcome.txt","emptySubFolder"' in folder "/baseFolder/"
		Then the downloaded zip file is a zip32 file
		And the downloaded zip file contains a file named "welcome.txt" with the contents of "/baseFolder/welcome.txt" from "user0" data
		And the downloaded zip file contains a folder named "emptySubFolder/"

	Scenario: downloading (from folder) a small file and 2 nested directories returns a zip32
		Given using new dav path
		And user "user0" exists
		And user "user0" created a folder "/baseFolder"
		And User "user0" copies file "/welcome.txt" to "/baseFolder/welcome.txt"
		And user "user0" created a folder "/baseFolder/subFolder"
		And user "user0" created a folder "/baseFolder/subFolder/emptySubSubFolder"
		When user "user0" downloads zip file for entries '"welcome.txt","subFolder"' in folder "/baseFolder/"
		Then the downloaded zip file is a zip32 file
		And the downloaded zip file contains a file named "welcome.txt" with the contents of "/baseFolder/welcome.txt" from "user0" data
		And the downloaded zip file contains a folder named "subFolder/"
		And the downloaded zip file contains a folder named "subFolder/emptySubSubFolder/"

	Scenario: downloading (from folder) dir with 2 small files returns a zip32
		Given using new dav path
		And user "user0" exists
		And user "user0" created a folder "/baseFolder"
		And user "user0" created a folder "/baseFolder/sparseFolder"
		And User "user0" copies file "/welcome.txt" to "/baseFolder/sparseFolder/welcome.txt"
		And User "user0" copies file "/welcome.txt" to "/baseFolder/sparseFolder/welcome2.txt"
		When user "user0" downloads zip file for entries '"sparseFolder"' in folder "/baseFolder/"
		Then the downloaded zip file is a zip32 file
		And the downloaded zip file contains a folder named "sparseFolder/"
		And the downloaded zip file contains a file named "sparseFolder/welcome.txt" with the contents of "/baseFolder/sparseFolder/welcome.txt" from "user0" data
		And the downloaded zip file contains a file named "sparseFolder/welcome2.txt" with the contents of "/baseFolder/sparseFolder/welcome2.txt" from "user0" data

	Scenario: downloading (from folder) dir with a small file and a directory returns a zip32
		Given using new dav path
		And user "user0" exists
		And user "user0" created a folder "/baseFolder"
		And user "user0" created a folder "/baseFolder/sparseFolder"
		And User "user0" copies file "/welcome.txt" to "/baseFolder/sparseFolder/welcome.txt"
		And user "user0" created a folder "/baseFolder/sparseFolder/emptySubFolder"
		When user "user0" downloads zip file for entries '"sparseFolder"' in folder "/baseFolder/"
		Then the downloaded zip file is a zip32 file
		And the downloaded zip file contains a folder named "sparseFolder/"
		And the downloaded zip file contains a file named "sparseFolder/welcome.txt" with the contents of "/baseFolder/sparseFolder/welcome.txt" from "user0" data
		And the downloaded zip file contains a folder named "sparseFolder/emptySubFolder/"

	Scenario: downloading (from folder) dir with a small file and 2 nested directories returns a zip32
		Given using new dav path
		And user "user0" exists
		And user "user0" created a folder "/baseFolder"
		And user "user0" created a folder "/baseFolder/sparseFolder"
		And User "user0" copies file "/welcome.txt" to "/baseFolder/sparseFolder/welcome.txt"
		And user "user0" created a folder "/baseFolder/sparseFolder/subFolder"
		And user "user0" created a folder "/baseFolder/sparseFolder/subFolder/emptySubSubFolder"
		When user "user0" downloads zip file for entries '"sparseFolder"' in folder "/baseFolder/"
		Then the downloaded zip file is a zip32 file
		And the downloaded zip file contains a folder named "sparseFolder/"
		And the downloaded zip file contains a file named "sparseFolder/welcome.txt" with the contents of "/baseFolder/sparseFolder/welcome.txt" from "user0" data
		And the downloaded zip file contains a folder named "sparseFolder/subFolder/"
		And the downloaded zip file contains a folder named "sparseFolder/subFolder/emptySubSubFolder/"
