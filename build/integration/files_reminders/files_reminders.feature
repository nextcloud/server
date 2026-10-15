# SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-only
Feature: Files reminders

  Background:
    Given using api version "2"

  Scenario: Set a reminder with a past due date
    Given user "user0" exists
    Given As an "user0"
    Given User "user0" uploads file "data/textfile.txt" to "/file.txt"
    When the user sets a reminder for "/file.txt" with due date "2000-01-01T00:00:00Z"
    Then the OCS status code should be "400"
    Then the user sees the reminder for "/file.txt" is not set

  Scenario: Set a reminder with a valid due date
    Given user "user1" exists
    Given As an "user1"
    Given User "user1" uploads file "data/textfile.txt" to "/file.txt"
    When the user sets a reminder for "/file.txt" with due date "2100-01-01T00:00:00Z"
    Then the OCS status code should be "201"
    Then the user sees the reminder for "/file.txt" is set to "2100-01-01T00:00:00+00:00"

  Scenario: Remove a reminder
    Given user "user2" exists
    Given As an "user2"
    Given User "user2" uploads file "data/textfile.txt" to "/file.txt"
    When the user sets a reminder for "/file.txt" with due date "2100-01-01T00:00:00Z"
    Then the OCS status code should be "201"
    Then the user sees the reminder for "/file.txt" is set to "2100-01-01T00:00:00+00:00"
    When the user removes the reminder for "/file.txt"
    Then the OCS status code should be "200"
    Then the user sees the reminder for "/file.txt" is not set
