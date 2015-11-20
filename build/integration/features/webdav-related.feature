Feature: sharing
  Background:
	Given using api version "1"

  Scenario: moving a file old way
	Given using dav path "remote.php/webdav"
	And As an "admin"
	And user "user0" exists
	When User "user0" moves file "/textfile0.txt" to "/FOLDER/textfile0.txt"
	Then the HTTP status code should be "201"













