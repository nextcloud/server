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

  Scenario: Cache routes from routes.php with a user in a group without some apps enabled
    Given invoking occ with "config:system:set memcache.local --value \OC\Memcache\APCu"
    And the command was successful
    And route "api/v1/location" of app "weather_status" is defined in routes.php
    And app "weather_status" enabled state will be restored once the scenario finishes
    And invoking occ with "app:enable weather_status --groups group1"
    And the command was successful
    When Logging in using web as "user2"
    And sending "GET" with exact url to "/apps/testing/clean_apcu_cache.php"
    And Sending a "GET" to "/index.php/apps/files" with requesttoken
    And the HTTP status code should be "200"
    Then As an "user2"
    And sending "GET" to "/apps/weather_status/api/v1/location"
    And the HTTP status code should be "412"
    And As an "user1"
    And sending "GET" to "/apps/weather_status/api/v1/location"
    And the OCS status code should be "200"
    And the HTTP status code should be "200"
