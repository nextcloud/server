Feature: trashbin
	Background:
		Given using api version "1"
		And using new dav path
		And As an "admin"
		And app "files_trashbin" is enabled

	Scenario: deleting a file moves it to trashbin
		Given As an "admin"
		And user "user0" exists
		When User "user0" deletes file "/textfile0.txt"
		Then user "user0" in trash folder "/" should have 1 element
		And user "user0" in trash folder "/" should have the following elements
			| textfile0.txt |

	Scenario: clearing the trashbin
		Given As an "admin"
		And user "user0" exists
		When User "user0" deletes file "/textfile0.txt"
		And User "user0" empties trashbin
		Then user "user0" in trash folder "/" should have 0 elements

	Scenario: restoring file from trashbin
		Given As an "admin"
		And user "user0" exists
		When User "user0" deletes file "/textfile0.txt"
		And user "user0" in restores "/textfile0.txt" from trash
		Then user "user0" in trash folder "/" should have 0 elements
		And as "user0" the file "/textfile0.txt" exists

	Scenario: deleting and restoring a folder
		Given As an "admin"
		And user "user0" exists
		When User "user0" created a folder "/testfolder"
		And User "user0" moves file "/textfile0.txt" to "/testfolder/textfile0.txt"
		And as "user0" the file "/testfolder/textfile0.txt" exists
		And User "user0" deletes file "/testfolder"
		And user "user0" in trash folder "/" should have 1 element
		And user "user0" in trash folder "/" should have the following elements
			| testfolder |
		And user "user0" in trash folder "/testfolder" should have 1 element
		And user "user0" in trash folder "/testfolder" should have the following elements
			| textfile0.txt |
		And user "user0" in restores "/testfolder" from trash
		Then user "user0" in trash folder "/" should have 0 elements
		And as "user0" the file "/testfolder/textfile0.txt" exists

	Scenario: deleting a file from a subfolder and restoring it moves it back to the subfolder
		Given As an "admin"
		And user "user0" exists
		When User "user0" created a folder "/testfolder"
		And User "user0" moves file "/textfile0.txt" to "/testfolder/textfile0.txt"
		And as "user0" the file "/testfolder/textfile0.txt" exists
		And User "user0" deletes file "/testfolder/textfile0.txt"
		And user "user0" in trash folder "/" should have 1 element
		And user "user0" in trash folder "/" should have the following elements
			| textfile0.txt |
		And user "user0" in restores "/textfile0.txt" from trash
		Then user "user0" in trash folder "/" should have 0 elements
		And as "user0" the file "/textfile0.txt" does not exist
		And as "user0" the file "/testfolder/textfile0.txt" exists

	Scenario: deleting and a folder and restoring a file inside it
		Given As an "admin"
		And user "user0" exists
		When User "user0" created a folder "/testfolder"
		And User "user0" moves file "/textfile0.txt" to "/testfolder/textfile0.txt"
		And as "user0" the file "/testfolder/textfile0.txt" exists
		And User "user0" deletes file "/testfolder"
		And user "user0" in trash folder "/" should have 1 element
		And user "user0" in trash folder "/" should have the following elements
			| testfolder |
		And user "user0" in trash folder "/testfolder" should have 1 element
		And user "user0" in trash folder "/testfolder" should have the following elements
			| textfile0.txt |
		And user "user0" in restores "/testfolder/textfile0.txt" from trash
		Then user "user0" in trash folder "/" should have 1 elements
		And user "user0" in trash folder "/testfolder" should have 0 element
		And as "user0" the file "/textfile0.txt" exists


