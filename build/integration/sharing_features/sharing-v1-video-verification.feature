@Talk
Feature: sharing
  Background:
    Given using api version "1"
    Given using old dav path
    Given invoking occ with "app:enable --force spreed"
    Given the command was successful

  Scenario: Creating a link share with send password by Talk
    Given user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 3 |
      | password | secret |
      | sendPasswordByTalk | true |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Enabling send password by Talk in a link share
    Given user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 3 |
    And Updating last share with
      | password | secret |
      | sendPasswordByTalk | true |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Enabling send password by Talk with different password in a link share
    Given user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 3 |
      | password | secret |
    And Updating last share with
      | password | another secret |
      | sendPasswordByTalk | true |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "another secret" can be downloaded

  Scenario: Enabling send password by Talk with different password set after creation in a link share
    Given user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 3 |
    And Updating last share with
      | password | secret |
    And Updating last share with
      | password | another secret |
      | sendPasswordByTalk | true |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "another secret" can be downloaded

  Scenario: Enabling send password by Talk with same password in a link share
    Given user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 3 |
      | password | secret |
    And Updating last share with
      | password | secret |
      | sendPasswordByTalk | true |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Enabling send password by Talk with same password set after creation in a link share
    Given user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 3 |
    And Updating last share with
      | password | secret |
    And Updating last share with
      | password | secret |
      | sendPasswordByTalk | true |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Enabling send password by Talk without updating password in a link share
    Given user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 3 |
      | password | secret |
    And Updating last share with
      | sendPasswordByTalk | true |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Enabling send password by Talk without updating password set after creation in a link share
    Given user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 3 |
    And Updating last share with
      | password | secret |
    And Updating last share with
      | sendPasswordByTalk | true |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Enabling send password by Talk with no password in a link share
    Given user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 3 |
    And Updating last share with
      | sendPasswordByTalk | true |
    Then the OCS status code should be "400"
    And the HTTP status code should be "200"
    And last share can be downloaded

  Scenario: Enabling send password by Talk with no password removed after creation in a link share
    Given user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 3 |
      | password | secret |
    And Updating last share with
      | password | |
    And Updating last share with
      | sendPasswordByTalk | true |
    Then the OCS status code should be "400"
    And the HTTP status code should be "200"
    And last share can be downloaded

  Scenario: Disabling send password by Talk without setting new password in a link share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 3 |
      | password | secret |
      | sendPasswordByTalk | true |
    And Updating last share with
      | sendPasswordByTalk | false |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Disabling send password by Talk without setting new password set after creation in a link share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 3 |
    And Updating last share with
      | password | secret |
      | sendPasswordByTalk | true |
    And Updating last share with
      | sendPasswordByTalk | false |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Disabling send password by Talk setting same password in a link share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 3 |
      | password | secret |
      | sendPasswordByTalk | true |
    And Updating last share with
      | password | secret |
      | sendPasswordByTalk | false |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Disabling send password by Talk setting same password set after creation in a link share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 3 |
    And Updating last share with
      | password | secret |
      | sendPasswordByTalk | true |
    And Updating last share with
      | password | secret |
      | sendPasswordByTalk | false |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Disabling send password by Talk setting new password in a link share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 3 |
      | password | secret |
      | sendPasswordByTalk | true |
    And Updating last share with
      | password | another secret |
      | sendPasswordByTalk | false |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "another secret" can be downloaded

  Scenario: Disabling send password by Talk setting new password set after creation in a link share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 3 |
    And Updating last share with
      | password | secret |
      | sendPasswordByTalk | true |
    And Updating last share with
      | password | another secret |
      | sendPasswordByTalk | false |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "another secret" can be downloaded





  Scenario: Creating a mail share with send password by Talk
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 4 |
      | shareWith | dummy@test.com |
      | password | secret |
      | sendPasswordByTalk | true |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Enabling send password by Talk in a mail share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 4 |
      | shareWith | dummy@test.com |
    And Updating last share with
      | password | secret |
      | sendPasswordByTalk | true |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Enabling send password by Talk with different password in a mail share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 4 |
      | shareWith | dummy@test.com |
      | password | secret |
    And Updating last share with
      | password | another secret |
      | sendPasswordByTalk | true |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "another secret" can be downloaded

  Scenario: Enabling send password by Talk with different password set after creation in a mail share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 4 |
      | shareWith | dummy@test.com |
    And Updating last share with
      | password | secret |
    And Updating last share with
      | password | another secret |
      | sendPasswordByTalk | true |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "another secret" can be downloaded

  Scenario: Enabling send password by Talk with same password in a mail share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 4 |
      | shareWith | dummy@test.com |
      | password | secret |
    And Updating last share with
      | password | secret |
      | sendPasswordByTalk | true |
    Then the OCS status code should be "400"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Enabling send password by Talk with same password set after creation in a mail share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 4 |
      | shareWith | dummy@test.com |
    And Updating last share with
      | password | secret |
    And Updating last share with
      | password | secret |
      | sendPasswordByTalk | true |
    Then the OCS status code should be "400"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Enabling send password by Talk without updating password in a mail share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 4 |
      | shareWith | dummy@test.com |
      | password | secret |
    And Updating last share with
      | sendPasswordByTalk | true |
    Then the OCS status code should be "400"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Enabling send password by Talk without updating password set after creation in a mail share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 4 |
      | shareWith | dummy@test.com |
    And Updating last share with
      | password | secret |
    And Updating last share with
      | sendPasswordByTalk | true |
    Then the OCS status code should be "400"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Enabling send password by Talk with no password in a mail share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 4 |
      | shareWith | dummy@test.com |
    And Updating last share with
      | sendPasswordByTalk | true |
    Then the OCS status code should be "400"
    And the HTTP status code should be "200"
    And last share can be downloaded

  Scenario: Enabling send password by Talk with no password removed after creation in a mail share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 4 |
      | shareWith | dummy@test.com |
      | password | secret |
    And Updating last share with
      | password | |
    And Updating last share with
      | sendPasswordByTalk | true |
    Then the OCS status code should be "400"
    And the HTTP status code should be "200"
    And last share can be downloaded

  Scenario: Disabling send password by Talk without setting new password in a mail share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 4 |
      | shareWith | dummy@test.com |
      | password | secret |
      | sendPasswordByTalk | true |
    And Updating last share with
      | sendPasswordByTalk | false |
    Then the OCS status code should be "400"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Disabling send password by Talk without setting new password set after creation in a mail share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 4 |
      | shareWith | dummy@test.com |
    And Updating last share with
      | password | secret |
      | sendPasswordByTalk | true |
    And Updating last share with
      | sendPasswordByTalk | false |
    Then the OCS status code should be "400"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Disabling send password by Talk setting same password in a mail share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 4 |
      | shareWith | dummy@test.com |
      | password | secret |
      | sendPasswordByTalk | true |
    And Updating last share with
      | password | secret |
      | sendPasswordByTalk | false |
    Then the OCS status code should be "400"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Disabling send password by Talk setting same password set after creation in a mail share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 4 |
      | shareWith | dummy@test.com |
    And Updating last share with
      | password | secret |
      | sendPasswordByTalk | true |
    And Updating last share with
      | password | secret |
      | sendPasswordByTalk | false |
    Then the OCS status code should be "400"
    And the HTTP status code should be "200"
    And last share with password "secret" can be downloaded

  Scenario: Disabling send password by Talk setting new password in a mail share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 4 |
      | shareWith | dummy@test.com |
      | password | secret |
      | sendPasswordByTalk | true |
    And Updating last share with
      | password | another secret |
      | sendPasswordByTalk | false |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "another secret" can be downloaded

  Scenario: Disabling send password by Talk setting new password set after creation in a mail share
    Given dummy mail server is listening
    And user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 4 |
      | shareWith | dummy@test.com |
    And Updating last share with
      | password | secret |
      | sendPasswordByTalk | true |
    And Updating last share with
      | password | another secret |
      | sendPasswordByTalk | false |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share with password "another secret" can be downloaded
