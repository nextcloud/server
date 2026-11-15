# SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
Feature: config-lexicon
  Background:
    Given user "user0" exists

  Scenario: Setting an integer user setting from the lexicon with a valid value
    When invoking occ with "user:setting user0 viewer volume 42"
    Then the command was successful
    When invoking occ with "user:setting user0 viewer volume"
    Then the command was successful
    And the command output contains the text "42"

  Scenario: Setting an integer user setting from the lexicon with a non-integer value
    When invoking occ with "user:setting user0 viewer volume abc"
    Then the command failed with exit code 1
    And the command error output contains the text "Value is not an integer"

  Scenario: Setting a boolean user setting from the lexicon with a valid value
    When invoking occ with "user:setting user0 settings user_list_show_storage_path yes"
    Then the command was successful

  Scenario: Setting a boolean user setting from the lexicon with a non-boolean value
    When invoking occ with "user:setting user0 settings user_list_show_storage_path maybe"
    Then the command failed with exit code 1
    And the command error output contains the text "Value cannot be converted to boolean"
