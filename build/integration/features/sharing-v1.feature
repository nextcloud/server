Feature: sharing
  Background:
    Given using api version "1"

  Scenario: Creating a new share with user
    Given user "user0" exists
    And user "user1" exists
    And As an "user0"
    When sending "POST" to "/apps/files_sharing/api/v1/shares" with
      | path | welcome.txt |
      | shareWith | user1 |
      | shareType | 0 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"

  Scenario: Creating a share with a group
    Given user "user0" exists
    And user "user1" exists
    And group "sharing-group" exists
    And As an "user0"
    When sending "POST" to "/apps/files_sharing/api/v1/shares" with
      | path | welcome.txt |
      | shareWith | sharing-group |
      | shareType | 1 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"

  Scenario: Creating a new public share
    Given user "user0" exists
    And As an "user0"
    When creating a public share with
      | path | welcome.txt |
      | shareType | 3 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Public shared file "welcome.txt" can be downloaded

  Scenario: Creating a new public share with password
    Given user "user0" exists
    And As an "user0"
    When creating a public share with
      | path | welcome.txt |
      | shareType | 3 |
      | password | publicpw |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Public shared file "welcome.txt" with password "publicpw" can be downloaded

  Scenario: Creating a new public share with password and adding an expiration date
    Given user "user0" exists
    And As an "user0"
    When creating a public share with
      | path | welcome.txt |
      | shareType | 3 |
      | password | publicpw |
    And Adding expiration date to last share
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Public shared file "welcome.txt" with password "publicpw" can be downloaded

  Scenario: getting all shares of a user using that user
    Given user "user0" exists
    And user "user1" exists
    And file "textfile0.txt" from user "user0" is shared with user "user1"
    And As an "user0"
    When sending "GET" to "/apps/files_sharing/api/v1/shares"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And File "textfile0.txt" should be included in the response

  Scenario: getting all shares of a user using another user
    Given user "user0" exists
    And user "user1" exists
    And file "textfile0.txt" from user "user0" is shared with user "user1"
    And As an "admin"
    When sending "GET" to "/apps/files_sharing/api/v1/shares"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And File "textfile0.txt" should not be included in the response

  Scenario: getting all shares of a file
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And user "user3" exists
    And file "textfile0.txt" from user "user0" is shared with user "user1"
    And file "textfile0.txt" from user "user0" is shared with user "user2"
    And As an "user0"
    When sending "GET" to "/apps/files_sharing/api/v1/shares?path=textfile0.txt"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And User "user1" should be included in the response
    And User "user2" should be included in the response
    And User "user3" should not be included in the response

  Scenario: getting all shares of a file with reshares
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And user "user3" exists
    And file "textfile0.txt" from user "user0" is shared with user "user1"
    And file "textfile0.txt" from user "user1" is shared with user "user2"
    And As an "user0"
    When sending "GET" to "/apps/files_sharing/api/v1/shares?reshares=true&path=textfile0.txt"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And User "user1" should be included in the response
    And User "user2" should be included in the response
    And User "user3" should not be included in the response

  Scenario: getting share info of a share
    Given user "user0" exists
    And user "user1" exists
    And file "textfile0.txt" from user "user0" is shared with user "user1"
    And As an "user0"
    When Getting info of last share
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Share fields of last share match with
      | id | A_NUMBER |
      | item_type | file |
      | item_source | A_NUMBER |
      | share_type | 0 |
      | share_with | user1 |
      | file_source | A_NUMBER |
      | file_target | /textfile0.txt |
      | path | /textfile0.txt |
      | permissions | 23 |
      | stime | A_NUMBER |
      | storage | A_NUMBER |
      | mail_send | 0 |
      | uid_owner | user0 |
      | storage_id | home::user0 |
      | file_parent | A_NUMBER |
      | share_with_displayname | user1 |
      | displayname_owner | user0 |

  Scenario: delete a share
    Given user "user0" exists
    And user "user1" exists
    And file "textfile0.txt" from user "user0" is shared with user "user1"
    And As an "user0"
    When Deleting last share
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"











