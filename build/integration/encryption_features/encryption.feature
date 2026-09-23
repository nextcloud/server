# SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
Feature: encryption
  Background:
    Given using api version "1"
    And using new dav path
    And invoking occ with "app:enable encryption"
    And the command was successful
    And invoking occ with "encryption:enable-master-key" with input "y"
    And the command was successful
    And invoking occ with "encryption:enable"
    And the command was successful

  Scenario: Upload and download a file spanning several encrypted blocks
    Given user "user0" exists
    And As an "user0"
    When User "user0" adds a file of 20000 bytes to "/big.bin"
    Then the HTTP status code should be "201"
    And File "/big.bin" should have prop "d:getcontentlength" equal to "20000"
    When Downloading file "/big.bin"
    Then the HTTP status code should be "200"

  Scenario: Copy a file spanning several encrypted blocks
    Given user "user0" exists
    And As an "user0"
    And User "user0" adds a file of 20000 bytes to "/big.bin"
    When User "user0" copies file "/big.bin" to "/copy.bin"
    Then the HTTP status code should be "201"
    And File "/copy.bin" should have prop "d:getcontentlength" equal to "20000"
    When Downloading file "/copy.bin"
    Then the HTTP status code should be "200"

  Scenario: Copy a file over an existing file
    Given user "user0" exists
    And As an "user0"
    And User "user0" uploads file with content "the source content" to "/source.txt"
    And User "user0" uploads file with content "the target content" to "/target.txt"
    When User "user0" copies file "/source.txt" to "/target.txt"
    Then the HTTP status code should be "204"
    When Downloading file "/target.txt"
    Then the HTTP status code should be "200"
    And Downloaded content should be "the source content"

  Scenario: Copy a file that was written several times
    Given user "user0" exists
    And As an "user0"
    And User "user0" uploads file with content "the first content" to "/source.txt"
    And User "user0" uploads file with content "the second content" to "/source.txt"
    When User "user0" copies file "/source.txt" to "/copy.txt"
    Then the HTTP status code should be "201"
    When Downloading file "/copy.txt"
    Then the HTTP status code should be "200"
    And Downloaded content should be "the second content"

  # With "part_file_in_storage" disabled the part file is written to the user
  # home while the target lives on another storage, so the upload has to read the
  # part file back to move it over. A part file never has a file cache entry, so
  # both the encrypted version and the unencrypted size of the written blocks
  # have to be known without one.
  @local_storage
  Scenario: Upload to an external storage while the part file is kept in the user home
    Given invoking occ with "config:system:set part_file_in_storage --value false --type boolean"
    And the command was successful
    And user "user0" exists
    And As an "user0"
    When User "user0" uploads file "data/textfile.txt" to "/local_storage/textfile.txt"
    Then the HTTP status code should be "201"
    When Downloading file "/local_storage/textfile.txt"
    Then the HTTP status code should be "200"
    And Downloaded content should start with "This is a testfile."
    When User "user0" adds a file of 20000 bytes to "/local_storage/big.bin"
    Then the HTTP status code should be "201"
    And File "/local_storage/big.bin" should have prop "d:getcontentlength" equal to "20000"
    When Downloading file "/local_storage/big.bin"
    Then the HTTP status code should be "200"
