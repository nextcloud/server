Feature: sharing
  Background:
    Given using api version "1"
    Given using new dav path

# See sharing-v1-part3.feature

Scenario: Creating a new share of a file shows size and mtime
    Given user "user0" exists
    And user "user1" exists
    And As an "user0"
    And parameter "shareapi_default_permissions" of app "core" is set to "7"
    When creating a share with
      | path | welcome.txt |
      | shareWith | user1 |
      | shareType | 0 |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Getting info of last share
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Share fields of last share match with
      | item_size | A_NUMBER |
      | item_mtime | A_NUMBER |

Scenario: Creating a new share of a file you own shows the file permissions
    Given user "user0" exists
    And user "user1" exists
    And As an "user0"
    And parameter "shareapi_default_permissions" of app "core" is set to "7"
    When creating a share with
      | path | welcome.txt |
      | shareWith | user1 |
      | shareType | 0 |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Getting info of last share
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Share fields of last share match with
      | item_permissions | 27 |
