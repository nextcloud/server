# SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-only
Feature: metadata

  Scenario: Setting metadata works
    Given user "user0" exists
    When User "user0" uploads file with content "AAA" to "/test.txt"
    And User "user0" sets the "metadata-files-live-photo" prop with value "metadata-value" on "/test.txt"
    Then User "user0" should see the prop "metadata-files-live-photo" equal to "metadata-value" for file "/test.txt"

  Scenario: Deleting metadata works
    Given user "user0" exists
    When User "user0" uploads file with content "AAA" to "/test.txt"
    And User "user0" sets the "metadata-files-live-photo" prop with value "metadata-value" on "/test.txt"
	  And User "user0" deletes the "metadata-files-live-photo" prop on "/test.txt"
	  Then User "user0" should not see the prop "metadata-files-live-photo" for file "/test.txt"
