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
			| /textfile0.txt |

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

