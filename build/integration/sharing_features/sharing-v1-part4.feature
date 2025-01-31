Feature: sharing
  Background:
    Given using api version "1"
    Given using new dav path

# See sharing-v1-part3.feature

# This is a regression test as in the past creating a file drop required creating with permissions=5
# and then afterwards update the share to permissions=4
Scenario: Directly create link share with CREATE only permissions (file drop)
    Given user "user0" exists
    And As an "user0"
    And user "user0" created a folder "/TMP"
    When creating a share with
      | path        | TMP |
      | shareType   |   3 |
      | permissions |   4 |
    And Getting info of last share
    Then Share fields of last share match with
      | uid_file_owner | user0 |
      | share_type     |     3 |
      | permissions    |     4 |

Scenario: Directly create email share with CREATE only permissions (file drop)
  Given user "user0" exists
  And As an "user0"
  And user "user0" created a folder "/TMP"
  When creating a share with
    | path        |               TMP |
    | shareType   |                 4 |
    | shareWith   | j.doe@example.com |
    | permissions |                 4 |
  And Getting info of last share
  Then Share fields of last share match with
    | uid_file_owner | user0 |
    | share_type     |     4 |
    | permissions    |     4 |

# This ensures the legacy behavior of sharing v1 is kept
Scenario: publicUpload overrides permissions
    Given user "user0" exists
    And As an "user0"
    And parameter "outgoing_server2server_share_enabled" of app "files_sharing" is set to "no"
    And user "user0" created a folder "/TMP"
    When creating a share with
      | path         |  TMP |
      | shareType    |    3 |
      | permissions  |    4 |
      | publicUpload | true |
    And Getting info of last share
    Then Share fields of last share match with
      | uid_file_owner | user0 |
      | share_type     |     3 |
      | permissions    |    15 |
    When creating a share with
      | path         |   TMP |
      | shareType    |     3 |
      | permissions  |     4 |
      | publicUpload | false |
    And Getting info of last share
    Then Share fields of last share match with
      | uid_file_owner | user0 |
      | share_type     |     3 |
      | permissions    |     1 |
