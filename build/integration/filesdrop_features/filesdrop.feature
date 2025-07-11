# SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
Feature: FilesDrop

  Scenario: Put file via files drop
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path | drop |
      | shareType | 3 |
      | publicUpload | true |
    And Updating last share with
      | permissions | 4 |
    When Dropping file "/a.txt" with "abc"
    And Downloading file "/drop/a.txt"
    Then Downloaded content should be "abc"

  Scenario: Put file same file multiple times via files drop
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path | drop |
      | shareType | 3 |
      | publicUpload | true |
    And Updating last share with
      | permissions | 4 |
    When Dropping file "/a.txt" with "abc"
    And Dropping file "/a.txt" with "def"
    And Downloading file "/drop/a.txt"
    Then Downloaded content should be "abc"
    And Downloading file "/drop/a (2).txt"
    Then Downloaded content should be "def"

  Scenario: Files drop forbid directory without a nickname
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path | drop |
      | shareType | 3 |
      | publicUpload | true |
    And Updating last share with
      | permissions | 4 |
    When Dropping file "/folder/a.txt" with "abc"
    Then the HTTP status code should be "400"

  Scenario: Files drop forbid MKCOL without a nickname
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path | drop |
      | shareType | 3 |
      | publicUpload | true |
    And Updating last share with
      | permissions | 4 |
    When Creating folder "folder" in drop
    Then the HTTP status code should be "400"

  Scenario: Files drop allows MKCOL with a nickname
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path | drop |
      | shareType | 3 |
      | publicUpload | true |
    And Updating last share with
      | permissions | 4 |
    When Creating folder "folder" in drop as "nickname"
    Then the HTTP status code should be "201"

  Scenario: Files drop forbid subfolder creation without a nickname
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path | drop |
      | shareType | 3 |
      | publicUpload | true |
    And Updating last share with
      | permissions | 4 |
    When dropping file "/folder/a.txt" with "abc"
    Then the HTTP status code should be "400"

  Scenario: Files request drop
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path | drop |
      | shareType | 4 |
      | permissions | 4 |
      | attributes | [{"scope":"fileRequest","key":"enabled","value":true}] |
      | shareWith |  |
    When Dropping file "/folder/a.txt" with "abc" as "Alice"
    And Downloading file "/drop/Alice/folder/a.txt"
    Then Downloaded content should be "abc"

  Scenario: File drop uploading folder with name of file
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path | drop |
      | shareType | 4 |
      | permissions | 4 |
      | attributes | [{"scope":"fileRequest","key":"enabled","value":true}] |
      | shareWith |  |
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
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path | drop |
      | shareType | 4 |
      | permissions | 4 |
      | attributes | [{"scope":"fileRequest","key":"enabled","value":true}] |
      | shareWith |  |
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
    
  Scenario: Put file same file multiple times via files drop
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path | drop |
      | shareType | 4 |
      | permissions | 4 |
      | attributes | [{"scope":"fileRequest","key":"enabled","value":true}] |
      | shareWith |  |
    When Dropping file "/folder/a.txt" with "abc" as "Mallory"
    And Dropping file "/folder/a.txt" with "def" as "Mallory"
    # Ensure folder structure and that we only checked
    # for files duplicates, but merged the existing folders
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
      | path | drop |
      | shareType | 4 |
      | permissions | 4 |
      | shareWith |  |
      | attributes | [{"scope":"fileRequest","key":"enabled","value":true}] |
    When Dropping file "/folder/a.txt" with "abc" as "Mallory"
    When as "user0" the file "/drop/Mallory/folder/a.txt" exists
    And Downloading public folder "Mallory"
    Then the HTTP status code should be "405"
    And Downloading public folder "Mallory/folder"
    Then the HTTP status code should be "405"
    And Downloading public file "Mallory/folder/a.txt"
    Then the HTTP status code should be "405"

  Scenario: Files drop requires nickname if file request is enabled
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path | drop |
      | shareType | 4 |
      | permissions | 4 |
      | attributes | [{"scope":"fileRequest","key":"enabled","value":true}] |
      | shareWith |  |
    When Dropping file "/folder/a.txt" with "abc"
    Then the HTTP status code should be "400"

  Scenario: Files request drop with invalid nickname with slashes
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path | drop |
      | shareType | 4 |
      | permissions | 4 |
      | attributes | [{"scope":"fileRequest","key":"enabled","value":true}] |
      | shareWith |  |
    When Dropping file "/folder/a.txt" with "abc" as "Alice/Bob/Mallory"
    Then the HTTP status code should be "400"

  Scenario: Files request drop with invalid nickname with forbidden characters
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path | drop |
      | shareType | 4 |
      | permissions | 4 |
      | attributes | [{"scope":"fileRequest","key":"enabled","value":true}] |
      | shareWith |  |
    When Dropping file "/folder/a.txt" with "abc" as ".htaccess"
    Then the HTTP status code should be "400"

  Scenario: Files request drop with invalid nickname with forbidden characters
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/drop"
    And as "user0" creating a share with
      | path | drop |
      | shareType | 4 |
      | permissions | 4 |
      | attributes | [{"scope":"fileRequest","key":"enabled","value":true}] |
      | shareWith |  |
    When Dropping file "/folder/a.txt" with "abc" as ".Mallory"
    Then the HTTP status code should be "400"
