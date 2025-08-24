# SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
# SPDX-FileCopyrightText: 2016 ownCloud, Inc.
# SPDX-License-Identifier: AGPL-3.0-only
Feature: checksums

  Scenario: Uploading a file with checksum should work
    Given user "user0" exists
    When user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    Then The webdav response should have a status code "201"

  Scenario: Uploading a file with checksum should return the checksum in the propfind
    Given user "user0" exists
    And user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    When user "user0" request the checksum of "/myChecksumFile.txt" via propfind
    Then The webdav checksum should match "MD5:d70b40f177b14b470d1756a3c12b963a"

  Scenario: Uploading a file with checksum should return the checksum in the download header
    Given user "user0" exists
    And user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    When user "user0" downloads the file "/myChecksumFile.txt"
    Then The header checksum should match "MD5:d70b40f177b14b470d1756a3c12b963a"

  Scenario: Moving a file with checksum should return the checksum in the propfind
    Given user "user0" exists
    And user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    When User "user0" moved file "/myChecksumFile.txt" to "/myMovedChecksumFile.txt"
    And user "user0" request the checksum of "/myMovedChecksumFile.txt" via propfind
    Then The webdav checksum should match "MD5:d70b40f177b14b470d1756a3c12b963a"

  Scenario: Moving file with checksum should return the checksum in the download header
    Given user "user0" exists
    And user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    When User "user0" moved file "/myChecksumFile.txt" to "/myMovedChecksumFile.txt"
    And user "user0" downloads the file "/myMovedChecksumFile.txt"
    Then The header checksum should match "MD5:d70b40f177b14b470d1756a3c12b963a"

  Scenario: Copying a file with checksum should return the checksum in the propfind
    Given user "user0" exists
    And user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    When User "user0" copied file "/myChecksumFile.txt" to "/myChecksumFileCopy.txt"
    And user "user0" request the checksum of "/myChecksumFileCopy.txt" via propfind
    Then The webdav checksum should match "MD5:d70b40f177b14b470d1756a3c12b963a"

  Scenario: Copying file with checksum should return the checksum in the download header
    Given user "user0" exists
    And user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    When User "user0" copied file "/myChecksumFile.txt" to "/myChecksumFileCopy.txt"
    And user "user0" downloads the file "/myChecksumFileCopy.txt"
    Then The header checksum should match "MD5:d70b40f177b14b470d1756a3c12b963a"

  Scenario: Overwriting a file with checksum should remove the checksum and not return it in the propfind
    Given user "user0" exists
    And user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    When user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt"
    And user "user0" request the checksum of "/myChecksumFile.txt" via propfind
    Then The webdav checksum should be empty

  Scenario: Overwriting a file with checksum should remove the checksum and not return it in the download header
    Given user "user0" exists
    And user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    When user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt"
    And user "user0" downloads the file "/myChecksumFile.txt"
    Then The OC-Checksum header should not be there
