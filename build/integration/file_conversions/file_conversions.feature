# SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-only

Feature: conversions
	Background:
		Given using api version "2"
    Given using new dav path
    Given user "user0" exists

  Scenario: Converting a file works
    Given user "user0" uploads file "data/clouds.jpg" to "/image.jpg"
    Then as "user0" the file "/image.jpg" exists
    When user "user0" converts file "/image.jpg" to "image/png"
    Then the HTTP status code should be "201"
    Then the OCS status code should be "201"
    Then as "user0" the file "/image.png" exists

  Scenario: Converting a file to a given path works
    Given user "user0" uploads file "data/clouds.jpg" to "/image.jpg"
    And User "user0" created a folder "/folder"
    Then as "user0" the file "/image.jpg" exists
    Then as "user0" the folder "/folder" exists
    When user "user0" converts file "/image.jpg" to "image/png" and saves it to "/folder/image.png"
    Then the HTTP status code should be "201"
    Then the OCS status code should be "201"
    Then as "user0" the file "/folder/image.png" exists
    Then as "user0" the file "/image.png" does not exist

  Scenario: Converting a file path with overwrite
    Given user "user0" uploads file "data/clouds.jpg" to "/image.jpg"
    And user "user0" uploads file "data/green-square-256.png" to "/image.png"
    Then as "user0" the file "/image.jpg" exists
    Then as "user0" the file "/image.png" exists
    When user "user0" converts file "/image.jpg" to "image/png"
    Then the HTTP status code should be "201"
    Then the OCS status code should be "201"
    Then as "user0" the file "/image.jpg" exists
    Then as "user0" the file "/image.png" exists
    Then as "user0" the file "/image (2).png" exists

  Scenario: Converting a file path with overwrite to a given path
    Given user "user0" uploads file "data/clouds.jpg" to "/image.jpg"
    And User "user0" created a folder "/folder"
    And user "user0" uploads file "data/green-square-256.png" to "/folder/image.png"
    Then as "user0" the file "/image.jpg" exists
    Then as "user0" the folder "/folder" exists
    Then as "user0" the file "/folder/image.png" exists
    When user "user0" converts file "/image.jpg" to "image/png" and saves it to "/folder/image.png"
    Then the HTTP status code should be "201"
    Then the OCS status code should be "201"
    Then as "user0" the file "/folder/image.png" exists
    Then as "user0" the file "/folder/image (2).png" exists
    Then as "user0" the file "/image.png" does not exist
    Then as "user0" the file "/image.jpg" exists

  Scenario: Converting a file which does not exist fails
    When user "user0" converts file "/image.jpg" to "image/png"
    Then the HTTP status code should be "404"
    Then the OCS status code should be "404"
    Then as "user0" the file "/image.jpg" does not exist
    Then as "user0" the file "/image.png" does not exist

  Scenario: Converting a file to an invalid destination path fails
    Given user "user0" uploads file "data/clouds.jpg" to "/image.jpg"
    When user "user0" converts file "/image.jpg" to "image/png" and saves it to "/folder/image.png"
    Then the HTTP status code should be "404"
    Then the OCS status code should be "404"
    Then as "user0" the file "/image.jpg" exists
    Then as "user0" the file "/folder/image.png" does not exist

  Scenario: Converting a file to an invalid format fails
    Given user "user0" uploads file "data/clouds.jpg" to "/image.jpg"
    When user "user0" converts file "/image.jpg" to "image/invalid"
    Then the HTTP status code should be "500"
    Then the OCS status code should be "999"
    Then as "user0" the file "/image.jpg" exists
    Then as "user0" the file "/image.png" does not exist

Scenario: Converting a file to a given path without extension fails
    Given user "user0" uploads file "data/clouds.jpg" to "/image.jpg"
    And User "user0" created a folder "/folder"
    Then as "user0" the file "/image.jpg" exists
    Then as "user0" the folder "/folder" exists
    When user "user0" converts file "/image.jpg" to "image/png" and saves it to "/folder/image"
    Then the HTTP status code should be "400"
    Then the OCS status code should be "400"
    Then as "user0" the file "/folder/image.png" does not exist
    Then as "user0" the file "/image.png" does not exist

  @local_storage
  Scenario: Converting a file bigger than 100 MiB fails
    Given file "/image.jpg" of size 108003328 is created in local storage
    Then as "user0" the folder "/local_storage" exists
    Then as "user0" the file "/local_storage/image.jpg" exists
    When user "user0" converts file "/local_storage/image.jpg" to "image/png" and saves it to "/image.png"
    Then the HTTP status code should be "400"
    Then the OCS status code should be "400"
    Then as "user0" the file "/image.png" does not exist

  Scenario: Forbid conversion to a destination without create permission
    Given user "user1" exists
    # Share the folder with user1
    Given User "user0" created a folder "/folder"
    Then As an "user0"
    When creating a share with
      | path | folder |
      | shareWith | user1 |
      | shareType | 0 |
      | permissions | 1 |
    Then the OCS status code should be "200"
    And the HTTP status code should be "200"
    # Create the folder, upload the image
    Then As an "user1"
    Given user "user1" accepts last share
    Given as "user1" the folder "/folder" exists
    Given user "user1" uploads file "data/clouds.jpg" to "/image.jpg"
    Then as "user1" the file "/image.jpg" exists
    # Try to convert the image to a folder where user1 has no create permission
    When user "user1" converts file "/image.jpg" to "image/png" and saves it to "/folder/folder.png"
    Then the OCS status code should be "403" 
    And the HTTP status code should be "403"
    Then as "user1" the file "/folder/folder.png" does not exist
