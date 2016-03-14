Feature: sharees
  Background:
    Given using api version "1"

  Scenario: Search without exact match
    Given user "test" exists
    And user "Sharee1" exists
    And group "ShareeGroup" exists
    And As an "test"
    When getting sharees for
      | search | Sharee |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    And "users" sharees returned are
      | Sharee1 | 0 | Sharee1 |
    And "exact groups" sharees returned is empty
    And "groups" sharees returned are
      | ShareeGroup | 1 | ShareeGroup |
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty

  Scenario: Search without exact match not-exact casing
    Given user "test" exists
    And user "Sharee1" exists
    And group "ShareeGroup" exists
    And As an "test"
    When getting sharees for
      | search | sharee |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    And "users" sharees returned are
      | Sharee1 | 0 | Sharee1 |
    And "exact groups" sharees returned is empty
    And "groups" sharees returned are
      | ShareeGroup | 1 | ShareeGroup |
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty

  Scenario: Search with exact match
    Given user "test" exists
    And user "Sharee1" exists
    And group "ShareeGroup" exists
    And As an "test"
    When getting sharees for
      | search | Sharee1 |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    Then "exact users" sharees returned are
      | Sharee1 | 0 | Sharee1 |
    Then "users" sharees returned is empty
    Then "exact groups" sharees returned is empty
    Then "groups" sharees returned is empty
    Then "exact remotes" sharees returned is empty
    Then "remotes" sharees returned is empty

  Scenario: Search with exact match not-exact casing
    Given user "test" exists
    And user "Sharee1" exists
    And group "ShareeGroup" exists
    And As an "test"
    When getting sharees for
      | search | sharee1 |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    Then "exact users" sharees returned are
      | Sharee1 | 0 | Sharee1 |
    Then "users" sharees returned is empty
    Then "exact groups" sharees returned is empty
    Then "groups" sharees returned is empty
    Then "exact remotes" sharees returned is empty
    Then "remotes" sharees returned is empty

  Scenario: Search with exact match not-exact casing group
    Given user "test" exists
    And user "Sharee1" exists
    And group "ShareeGroup" exists
    And As an "test"
    When getting sharees for
      | search | shareegroup |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    Then "exact users" sharees returned is empty
    Then "users" sharees returned is empty
    Then "exact groups" sharees returned are
      | ShareeGroup | 1 | ShareeGroup |
    Then "groups" sharees returned is empty
    Then "exact remotes" sharees returned is empty
    Then "remotes" sharees returned is empty

  Scenario: Search with "self"
    Given user "test" exists
    And user "Sharee1" exists
    And group "ShareeGroup" exists
    And As an "Sharee1"
    When getting sharees for
      | search | Sharee1 |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    Then "exact users" sharees returned are
      | Sharee1 | 0 | Sharee1 |
    Then "users" sharees returned is empty
    Then "exact groups" sharees returned is empty
    Then "groups" sharees returned is empty
    Then "exact remotes" sharees returned is empty
    Then "remotes" sharees returned is empty

  Scenario: Remote sharee for files
    Given user "test" exists
    And user "Sharee1" exists
    And group "ShareeGroup" exists
    And As an "test"
    When getting sharees for
      | search | test@localhost |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    Then "exact users" sharees returned is empty
    Then "users" sharees returned is empty
    Then "exact groups" sharees returned is empty
    Then "groups" sharees returned is empty
    Then "exact remotes" sharees returned are
      | test@localhost | 6 | test@localhost |
    Then "remotes" sharees returned is empty

  Scenario: Remote sharee for calendars not allowed
    Given user "test" exists
    And user "Sharee1" exists
    And group "ShareeGroup" exists
    And As an "test"
    When getting sharees for
      | search | test@localhost |
      | itemType | calendar |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    Then "exact users" sharees returned is empty
    Then "users" sharees returned is empty
    Then "exact groups" sharees returned is empty
    Then "groups" sharees returned is empty
    Then "exact remotes" sharees returned is empty
    Then "remotes" sharees returned is empty
