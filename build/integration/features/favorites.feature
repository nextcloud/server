Feature: favorite
    Background:
        Given using api version "1"

    Scenario: Retrieving favorite info of a folder
        Given using dav path "remote.php/webdav"
        And As an "admin"
        And user "user0" exists
        When user "user0" favorites folder "/"
            |{http://owncloud.org/ns}favorite|
        Then As "user0" gets properties of folder "/" with
            |{http://owncloud.org/ns}favorite|
        And the single response should contain a property "{http://owncloud.org/ns}favorite" with value "1"

    Scenario: Retrieving favorite info of a file
        Given using dav path "remote.php/webdav"
        And As an "admin"
        And user "user0" exists
        When user "user0" favorites folder "/textfile0.txt"
            |{http://owncloud.org/ns}favorite|
        Then As "user0" gets properties of folder "/textfile0.txt" with
            |{http://owncloud.org/ns}favorite|
        And the single response should contain a property "{http://owncloud.org/ns}favorite" with value "1"