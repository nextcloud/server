# SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
Feature: log-condition

  Background:
    Given invoking occ with "config:system:set log.condition matches 0 users 0 --value admin"
    Then the command was successful

  Scenario: Accessing /status.php with log.condition
    When requesting "/status.php" with "GET"
    Then the HTTP status code should be "200"

  Scenario: Accessing /index.php with log.condition
    When requesting "/index.php" with "GET"
    Then the HTTP status code should be "200"

  Scenario: Accessing /remote.php/webdav with log.condition
    When requesting "/remote.php/webdav" with "GET"
    Then the HTTP status code should be "401"

  Scenario: Accessing /remote.php/dav with log.condition
    When requesting "/remote.php/dav" with "GET"
    Then the HTTP status code should be "401"

  Scenario: Accessing /ocs/v1.php with log.condition
    When requesting "/ocs/v1.php" with "GET"
    Then the HTTP status code should be "200"

  Scenario: Accessing /ocs/v2.php with log.condition
    When requesting "/ocs/v2.php" with "GET"
    Then the HTTP status code should be "404"

  Scenario: Accessing /public.php/webdav with log.condition
    When requesting "/public.php/webdav" with "GET"
    Then the HTTP status code should be "401"

  Scenario: Accessing /public.php/dav with log.condition
    When requesting "/public.php/dav" with "GET"
    Then the HTTP status code should be "503"
