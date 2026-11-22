@Guests
# SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
Feature: guests app
  Background:
    Given using api version "1"
    Given using old dav path
    Given invoking occ with "app:enable --force guests"
    Given the command was successful
    And user "user-guest@example.com" is a guest account user

  Scenario: Receive a share as a guests app user
    And user "user-guest@example.com" should see following elements
      | / |
    Given user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 0 |
      | shareWith | user-guest@example.com |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And user "user-guest@example.com" should see following elements
      | / |
      | /welcome.txt |
