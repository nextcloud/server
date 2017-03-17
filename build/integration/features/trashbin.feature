Feature: trashbin
	Background:
		Given using api version "1"
		And using old dav path
		And As an "admin"
		And app "files_trashbin" is enabled

	Scenario: deleting a file moves it to trashbin
		Given As an "admin"
		And user "user0" exists
		When User "user0" deletes file "/textfile0.txt"
		Then as "user0" the file "/textfile0.txt" exists in trash

