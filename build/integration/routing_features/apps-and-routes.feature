# SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
Feature: appmanagement
  Background:
    Given using api version "2"
    And user "user1" exists
    And user "user2" exists
    And group "group1" exists
    And user "user1" belongs to group "group1"

  Scenario: Enable app and test route
    Given As an "admin"
    And sending "DELETE" to "/cloud/apps/weather_status"
    And app "weather_status" is disabled
    When sending "GET" to "/apps/weather_status/api/v1/location"
    Then the OCS status code should be "998"
    And the HTTP status code should be "404"
    When sending "POST" to "/cloud/apps/weather_status"
    Then the OCS status code should be "200"
    And the HTTP status code should be "200"
    And app "weather_status" is enabled
    When sending "GET" to "/apps/weather_status/api/v1/location"
    Then the OCS status code should be "200"
    And the HTTP status code should be "200"
    Given As an "user1"
    When sending "GET" to "/apps/weather_status/api/v1/location"
    Then the OCS status code should be "200"
    And the HTTP status code should be "200"
    Given As an "user2"
    When sending "GET" to "/apps/weather_status/api/v1/location"
    Then the OCS status code should be "200"
    And the HTTP status code should be "200"

  Scenario: Enable app only for some groups
    Given As an "admin"
    And sending "DELETE" to "/cloud/apps/weather_status"
    And app "weather_status" is disabled
    When sending "GET" to "/apps/weather_status/api/v1/location"
    Then the OCS status code should be "998"
    And the HTTP status code should be "404"
    Given invoking occ with "app:enable weather_status --groups group1"
    Then the command was successful
    Given As an "user2"
    When sending "GET" to "/apps/weather_status/api/v1/location"
    Then the HTTP status code should be "412"
    Given As an "user1"
    When sending "GET" to "/apps/weather_status/api/v1/location"
    Then the OCS status code should be "200"
    And the HTTP status code should be "200"
    Given As an "admin"
    And sending "DELETE" to "/cloud/apps/weather_status"
    And app "weather_status" is disabled
