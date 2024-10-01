# SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
# SPDX-FileCopyrightText: 2015 ownCloud, Inc.
# SPDX-License-Identifier: AGPL-3.0-only
Feature: provisioning
  Background:
    Given using api version "2"

  Scenario: Getting an not existing user
    Given As an "admin"
    When sending "GET" to "/cloud/users/test"
    Then the HTTP status code should be "404"

  Scenario: get app info from app that does not exist
    Given As an "admin"
    When sending "GET" to "/cloud/apps/this_app_should_never_exist"
    Then the OCS status code should be "998"
    And the HTTP status code should be "404"

  Scenario: enable an app that does not exist
    Given As an "admin"
    When sending "POST" to "/cloud/apps/this_app_should_never_exist"
    Then the OCS status code should be "998"
    And the HTTP status code should be "404"

  Scenario: Searching by displayname in groups
    Given As an "admin"
    And user "user-in-group" with displayname "specific-name" exists
    And user "user-in-group2" with displayname "another-name" exists
    And user "user-not-in-group" with displayname "specific-name" exists
    And user "user-not-in-group2" with displayname "another-name" exists
    And group "group-search" exists
    And user "user-in-group" belongs to group "group-search"
    And user "user-in-group2" belongs to group "group-search"
    When sending "GET" to "/cloud/groups/group-search/users/details?offset=0&limit=25&search=ifi"
    Then the OCS status code should be "200"
    And the HTTP status code should be "200"
    And detailed users returned are
      | user-in-group |
