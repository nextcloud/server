# SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
Feature: avatar

  Background:
    Given user "user0" exists

  Scenario: get default user avatar
    When user "user0" gets avatar for user "user0"
    Then The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 0 |
    And last avatar is a square of size 512
    And last avatar is not a single color

  Scenario: get default user avatar as an anonymous user
    When user "anonymous" gets avatar for user "user0"
    Then The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 0 |
    And last avatar is a square of size 512
    And last avatar is not a single color

  Scenario: set square user avatar from file
    Given Logging in using web as "user0"
    When logged in user posts avatar from file "data/green-square-256.png"
    And user "user0" gets avatar for user "user0"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 1 |
    # Last avatar size is 512 by default when getting avatar without size parameter
    And last avatar is a square of size 512
    And last avatar is a single "#00FF00" color
    And user "anonymous" gets avatar for user "user0"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 1 |
    And last avatar is a square of size 512
    And last avatar is a single "#00FF00" color

  Scenario: set square user avatar from internal path
    Given user "user0" uploads file "data/green-square-256.png" to "/internal-green-square-256.png"
    And Logging in using web as "user0"
    When logged in user posts avatar from internal path "internal-green-square-256.png"
    And user "user0" gets avatar for user "user0" with size "64"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 1 |
    And last avatar is a square of size 64
    And last avatar is a single "#00FF00" color
    And user "anonymous" gets avatar for user "user0" with size "64"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 1 |
    And last avatar is a square of size 64
    And last avatar is a single "#00FF00" color

  Scenario: delete user avatar
    Given Logging in using web as "user0"
    And logged in user posts avatar from file "data/green-square-256.png"
    And user "user0" gets avatar for user "user0"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 1 |
    And last avatar is a square of size 512
    And last avatar is a single "#00FF00" color
    And user "anonymous" gets avatar for user "user0"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 1 |
    And last avatar is a square of size 512
    And last avatar is a single "#00FF00" color
    When logged in user deletes the user avatar
    Then user "user0" gets avatar for user "user0"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 0 |
    And last avatar is a square of size 512
    And last avatar is not a single color
    And user "anonymous" gets avatar for user "user0"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 0 |
    And last avatar is a square of size 512
    And last avatar is not a single color

  Scenario: get default guest avatar
    When user "user0" gets avatar for guest "guest0"
    Then The following headers should be set
      | Content-Type | image/png |
    And last avatar is a square of size 512
    And last avatar is not a single color

  Scenario: get default guest avatar as an anonymous user
    When user "anonymous" gets avatar for guest "guest0"
    Then The following headers should be set
      | Content-Type | image/png |
    And last avatar is a square of size 512
    And last avatar is not a single color
