# SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
Feature: sharing-public-preview
  Background:
    Given using api version "1"
    And using new dav path
    And user "user0" exists
    And As an "user0"
    And User "user0" created a folder "/preview-share"
    And User "user0" uploads file "data/green-square-256.png" to "/preview-share/image.png"

  Scenario: Getting the public preview of an image on a link share without password
    Given as "user0" creating a share with
      | path        | preview-share |
      | shareType   | 3             |
      | permissions | 1             |
    And the OCS status code should be "100"
    When getting the public preview of the last share for file "/image.png"
    Then the HTTP status code should be "200"
    And the response should be an image

  Scenario: Getting the public preview of an image on a password protected link share after authenticating
    Given as "user0" creating a share with
      | path        | preview-share |
      | shareType   | 3             |
      | permissions | 1             |
      | password    | publicpw      |
    And the OCS status code should be "100"
    And authenticating to the last public share with password "publicpw"
    When getting the public preview of the last share for file "/image.png"
    Then the HTTP status code should be "200"
    And the response should be an image

  Scenario: Getting the public preview of an image on a password protected link share is not possible without authenticating
    Given as "user0" creating a share with
      | path        | preview-share |
      | shareType   | 3             |
      | permissions | 1             |
      | password    | publicpw      |
    And the OCS status code should be "100"
    When getting the public preview of the last share for file "/image.png"
    Then the HTTP status code should be "404"
