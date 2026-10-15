# SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
Feature: sharing
  Background:
    Given using api version "1"
    Given using new dav path
    Given invoking occ with "app:enable --force activity"
    Given the command was successful
    Given user "user0" exists
    And Logging in using web as "user0"
    And Sending a "POST" to "/apps/activity/settings" with requesttoken
      | public_links_notification | 1 |
      | public_links_upload_notification | 1 |
      | notify_setting_batchtime | 0 |
      | activity_digest | 0 |

  Scenario: Creating a new mail share and check activity
    Given dummy mail server is listening
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 4 |
      | shareWith | dumy@test.com |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share can be downloaded
    Then last activity should be
      | app | files_sharing |
      | type | public_links |
      | object_type | files |
      | object_name | /welcome.txt |

  Scenario: Creating a new public share and check activity
    Given user "user0" exists
    And As an "user0"
    When creating a share with
      | path | welcome.txt |
      | shareType | 3 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last link share can be downloaded
    Then last activity should be
      | app | files_sharing |
      | type | public_links |
      | object_type | files |
      | object_name | /welcome.txt |
