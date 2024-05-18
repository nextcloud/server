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

  Scenario: Files drop ignores directory
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
    And Downloading file "/drop/a.txt"
    Then Downloaded content should be "abc"

  Scenario: Files drop forbis MKCOL
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
    Then the HTTP status code should be "405"
