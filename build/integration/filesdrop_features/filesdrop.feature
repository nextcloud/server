# SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
Feature: FilesDrop

  # Scenarios using shareType 3 (public link drop) do not require a nickname.
  # Scenarios using shareType 4 (file request / email share) require a nickname
  # when the fileRequest attribute is enabled, and files are stored under a
  # per-nickname subdirectory.

  Scenario: Put file via files drop
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path         | drop |
      | shareType    | 3    |
      | publicUpload | true |
    And Updating last share with
      | permissions | 4 |
    When Dropping file "/a.txt" with "abc"
    And Downloading file "/drop/a.txt"
    Then Downloaded content should be "abc"

  Scenario: Put same file multiple times via files drop (public link)
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path         | drop |
      | shareType    | 3    |
      | publicUpload | true |
    And Updating last share with
      | permissions | 4 |
    When Dropping file "/a.txt" with "abc"
    And Dropping file "/a.txt" with "def"
    And Downloading file "/drop/a.txt"
    Then Downloaded content should be "abc"
    And Downloading file "/drop/a (2).txt"
    Then Downloaded content should be "def"

  Scenario: Files request forbid directory without a nickname
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path         | drop                                                     |
      | shareType    | 3                                                        |
      | publicUpload | true                                                     |
      | attributes   | [{"scope":"fileRequest","key":"enabled","value":true}]   |
    And Updating last share with
      | permissions | 4 |
    When Dropping file "/folder/a.txt" with "abc"
    Then the HTTP status code should be "400"

  Scenario: Files drop allow MKCOL without a nickname
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path         | drop |
      | shareType    | 3    |
      | publicUpload | true |
    And Updating last share with
      | permissions | 4 |
    When Creating folder "folder" in drop
    Then the HTTP status code should be "201"

  Scenario: Files request forbid MKCOL without a nickname
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path         | drop                                                     |
      | shareType    | 3                                                        |
      | publicUpload | true                                                     |
      | attributes   | [{"scope":"fileRequest","key":"enabled","value":true}]   |
    And Updating last share with
      | permissions | 4 |
    When Creating folder "folder" in drop
    Then the HTTP status code should be "400"

  Scenario: Files request allows MKCOL with a nickname
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path         | drop                                                     |
      | shareType    | 3                                                        |
      | publicUpload | true                                                     |
      | attributes   | [{"scope":"fileRequest","key":"enabled","value":true}]   |
    And Updating last share with
      | permissions | 4 |
    When Creating folder "folder" in drop as "nickname"
    Then the HTTP status code should be "201"

  Scenario: Files request forbid subfolder creation without a nickname
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path         | drop                                                     |
      | shareType    | 3                                                        |
      | publicUpload | true                                                     |
      | attributes   | [{"scope":"fileRequest","key":"enabled","value":true}]   |
    And Updating last share with
      | permissions | 4 |
    When dropping file "/folder/a.txt" with "abc"
    Then the HTTP status code should be "400"

  Scenario: Files request drop
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path        | drop                                                     |
      | shareType   | 4                                                        |
      | permissions | 4                                                        |
      | attributes  | [{"scope":"fileRequest","key":"enabled","value":true}]   |
      | shareWith   |                                                          |
    When Dropping file "/folder/a.txt" with "abc" as "Alice"
    And Downloading file "/drop/Alice/folder/a.txt"
    Then Downloaded content should be "abc"

  Scenario: File drop uploading folder with name of file
    # When a file and a directory share the same name, the first upload keeps
    # the original name. Here "/folder" is uploaded as a plain file first, so
    # it retains the name "folder". The subsequent upload of "/folder/a.txt"
    # requires a directory also named "folder", which is deduplicated to
    # "folder (2)" because the plain file already occupies that name.
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path        | drop                                                     |
      | shareType   | 4                                                        |
      | permissions | 4                                                        |
      | attributes  | [{"scope":"fileRequest","key":"enabled","value":true}]   |
      | shareWith   |                                                          |
    When Dropping file "/folder" with "its a file" as "Alice"
    Then the HTTP status code should be "201"
    When Dropping file "/folder/a.txt" with "abc" as "Alice"
    Then the HTTP status code should be "201"
    When Downloading file "/drop/Alice/folder"
    Then the HTTP status code should be "200"
    And Downloaded content should be "its a file"
    When Downloading file "/drop/Alice/folder (2)/a.txt"
    Then Downloaded content should be "abc"

  Scenario: File drop uploading file with name of folder
    # Mirror of the previous scenario: the directory "/folder" is created first
    # by uploading "/folder/a.txt", so it retains the name "folder". The
    # subsequent upload of a plain file also named "/folder" is deduplicated
    # to "folder (2)".
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path        | drop                                                     |
      | shareType   | 4                                                        |
      | permissions | 4                                                        |
      | attributes  | [{"scope":"fileRequest","key":"enabled","value":true}]   |
      | shareWith   |                                                          |
    When Dropping file "/folder/a.txt" with "abc" as "Alice"
    Then the HTTP status code should be "201"
    When Dropping file "/folder" with "its a file" as "Alice"
    Then the HTTP status code should be "201"
    When Downloading file "/drop/Alice/folder/a.txt"
    Then the HTTP status code should be "200"
    And Downloaded content should be "abc"
    When Downloading file "/drop/Alice/folder (2)"
    Then the HTTP status code should be "200"
    And Downloaded content should be "its a file"

  Scenario: Put same file multiple times via files drop (file request with nickname)
    # Only files are deduplicated across repeated uploads from the same nickname.
    # Folders are merged, not duplicated: "Mallory (2)" and "folder (2)" must
    # not be created; only the conflicting file gets a "(2)" suffix.
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path        | drop                                                     |
      | shareType   | 4                                                        |
      | permissions | 4                                                        |
      | attributes  | [{"scope":"fileRequest","key":"enabled","value":true}]   |
      | shareWith   |                                                          |
    When Dropping file "/folder/a.txt" with "abc" as "Mallory"
    And Dropping file "/folder/a.txt" with "def" as "Mallory"
    Then as "user0" the folder "/drop/Mallory" exists
    Then as "user0" the folder "/drop/Mallory/folder" exists
    Then as "user0" the folder "/drop/Mallory (2)" does not exist
    Then as "user0" the folder "/drop/Mallory/folder (2)" does not exist
    Then as "user0" the file "/drop/Mallory/folder/a.txt" exists
    Then as "user0" the file "/drop/Mallory/folder/a (2).txt" exists
    And Downloading file "/drop/Mallory/folder/a.txt"
    Then Downloaded content should be "abc"
    And Downloading file "/drop/Mallory/folder/a (2).txt"
    Then Downloaded content should be "def"

  Scenario: Files drop prevents GET
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path        | drop                                                     |
      | shareType   | 4                                                        |
      | permissions | 4                                                        |
      | shareWith   |                                                          |
      | attributes  | [{"scope":"fileRequest","key":"enabled","value":true}]   |
    When Dropping file "/folder/a.txt" with "abc" as "Mallory"
    When as "user0" the file "/drop/Mallory/folder/a.txt" exists
    # Directory listings are blocked (405 Method Not Allowed)
    And Downloading public folder "Mallory"
    Then the HTTP status code should be "405"
    And Downloading public folder "Mallory/folder"
    Then the HTTP status code should be "405"
    # Individual files are not exposed at all (404 Not Found)
    And Downloading public file "Mallory/folder/a.txt"
    Then the HTTP status code should be "404"

  Scenario: Files drop requires nickname if file request is enabled
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path        | drop                                                     |
      | shareType   | 4                                                        |
      | permissions | 4                                                        |
      | attributes  | [{"scope":"fileRequest","key":"enabled","value":true}]   |
      | shareWith   |                                                          |
    When Dropping file "/folder/a.txt" with "abc"
    Then the HTTP status code should be "400"

  Scenario: Files request drop with invalid nickname containing slashes
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path        | drop                                                     |
      | shareType   | 4                                                        |
      | permissions | 4                                                        |
      | attributes  | [{"scope":"fileRequest","key":"enabled","value":true}]   |
      | shareWith   |                                                          |
    When Dropping file "/folder/a.txt" with "abc" as "Alice/Bob/Mallory"
    Then the HTTP status code should be "400"

  Scenario: Files request drop with invalid nickname matching a server file (.htaccess)
    # Nicknames that match web-server reserved filenames are blocked to prevent
    # accidental or malicious overwrite of server configuration files.
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path        | drop                                                     |
      | shareType   | 4                                                        |
      | permissions | 4                                                        |
      | attributes  | [{"scope":"fileRequest","key":"enabled","value":true}]   |
      | shareWith   |                                                          |
    When Dropping file "/folder/a.txt" with "abc" as ".htaccess"
    Then the HTTP status code should be "400"

  Scenario: Files request drop with invalid nickname starting with a dot
    # Dot-prefixed nicknames are blocked because they would create hidden
    # directories on POSIX filesystems, which is undesirable regardless of
    # the name being otherwise harmless (e.g. ".Mallory").
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path        | drop                                                     |
      | shareType   | 4                                                        |
      | permissions | 4                                                        |
      | attributes  | [{"scope":"fileRequest","key":"enabled","value":true}]   |
      | shareWith   |                                                          |
    When Dropping file "/folder/a.txt" with "abc" as ".Mallory"
    Then the HTTP status code should be "400"
