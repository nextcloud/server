Feature: cleanup-remote-storage
  Background:
    Given using api version "1"

  Scenario: cleanup remote storage with active storages
    Given Using server "LOCAL"
    And user "user0" exists
    Given Using server "REMOTE"
    And user "user1" exists
    # Rename file so it has a unique name in the target server (as the target
    # server may have its own /textfile0.txt" file)
    And User "user1" copies file "/textfile0.txt" to "/remote-share.txt"
    And User "user1" from server "REMOTE" shares "/remote-share.txt" with user "user0" from server "LOCAL"
    And Using server "LOCAL"
    # Accept and download the file to ensure that a storage is created for the
    # federated share
    And User "user0" from server "LOCAL" accepts last pending share
    And As an "user0"
    And Downloading file "/remote-share.txt"
    And the HTTP status code should be "200"
    When invoking occ with "sharing:cleanup-remote-storage"
    Then the command was successful
    And the command output contains the text "1 remote storage(s) need(s) to be checked"
    And the command output contains the text "1 remote share(s) exist"
    And the command output contains the text "no storages deleted"

  Scenario: cleanup remote storage with inactive storages
    Given Using server "LOCAL"
    And user "user0" exists
    Given Using server "REMOTE"
    And user "user1" exists
    # Rename file so it has a unique name in the target server (as the target
    # server may have its own /textfile0.txt" file)
    And User "user1" copies file "/textfile0.txt" to "/remote-share.txt"
    And User "user1" from server "REMOTE" shares "/remote-share.txt" with user "user0" from server "LOCAL"
    And Using server "LOCAL"
    # Accept and download the file to ensure that a storage is created for the
    # federated share
    And User "user0" from server "LOCAL" accepts last pending share
    And As an "user0"
    And Downloading file "/remote-share.txt"
    And the HTTP status code should be "200"
    And Using server "REMOTE"
    And As an "user1"
    And Deleting last share
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    When Using server "LOCAL"
    And invoking occ with "sharing:cleanup-remote-storage"
    Then the command was successful
    And the command output contains the text "1 remote storage(s) need(s) to be checked"
    And the command output contains the text "0 remote share(s) exist"
    And the command output contains the text "deleted 1 storage"
