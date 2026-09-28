# SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
Feature: sharing-accept
  Background:
    Given using api version "1"
    Given using old dav path

  Scenario: Accepting a non-existent share
    Given user "user0" exists
    And As an "user0"
    When sending "GET" with exact url to "/index.php/apps/files_sharing/accept/ocinternal:999999"
    Then the HTTP status code should be "404"

  Scenario: Accepting a share with an invalid share ID
    Given user "user0" exists
    And As an "user0"
    When sending "GET" with exact url to "/index.php/apps/files_sharing/accept/invalid:format"
    Then the HTTP status code should be "404"

  Scenario: Accepting a valid share
    Given user "user0" exists
    And user "user1" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareWith | user1 |
      | shareType | 0 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And As an "user1"
    When accepting last share via the accept endpoint
    Then the HTTP status code should be "200"

  Scenario: Accepting a share as a different user
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareWith | user1 |
      | shareType | 0 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And As an "user2"
    When accepting last share via the accept endpoint
    Then the HTTP status code should be "404"

  Scenario: Accepting a share that is not a user share
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And As an "user0"
    And User user0 created a folder drop
    When creating a share with
      | path | drop |
      | shareType | 3 |
      | publicUpload | true |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And As an "user2"
    When accepting last share via the accept endpoint
    Then the HTTP status code should be "404"
