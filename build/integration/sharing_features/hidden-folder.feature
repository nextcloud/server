Feature: Hidden folder sharing
  Background:
    Given using api version "1"
    And user "user0" exists
    And system parameter "instanceid" is set to "dummy"
    And User "user0" created a folder "/.hidden_instance"
    And system parameter "instanceid" is set to "instance"

  Scenario: Share the hidden folder fails
    Given user "user0" exists
    And user "user1" exists
    And As an "user0"
    When creating a share with
      | path | .hidden_instance |
      | shareWith | user1 |
      | shareType | 0 |
    Then the OCS status code should be "403"
    And the HTTP status code should be "200"
