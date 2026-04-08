# SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
  Scenario: download restrictions can not be dropped
  As an "admin"
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And User "user0" uploads file with content "foo" to "/tmp.txt"
    And As an "user0"
    And creating a share with
      | path        | /tmp.txt |
      | shareType   | 0 |
      | shareWith   | user1 |
      | permissions | 17 |
      | attributes  | [{"scope":"permissions","key":"download","value":false}] |
    And As an "user1"
    And accepting last share
    When Getting info of last share
    Then Share fields of last share match with
      | uid_owner      | user0 |
      | uid_file_owner | user0 |
      | permissions    | 17    |
      | attributes     | [{"scope":"permissions","key":"download","value":false}] |
    When creating a share with
      | path        | /tmp.txt |
      | shareType   | 0        |
      | shareWith   | user2    |
      | permissions | 1        |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    When As an "user2"
    And accepting last share
    And Getting info of last share
    Then Share fields of last share match with
      | share_type     | 0     |
      | permissions    | 1     |
      | uid_owner      | user1 |
      | uid_file_owner | user0 |
      | attributes     | [{"scope":"permissions","key":"download","value":false}] |

  Scenario: download restrictions can not be dropped when re-sharing even on link shares
  As an "admin"
    Given user "user0" exists
    And user "user1" exists
    And User "user0" uploads file with content "foo" to "/tmp.txt"
    And As an "user0"
    And creating a share with
      | path        | /tmp.txt |
      | shareType   | 0 |
      | shareWith   | user1 |
      | permissions | 17 |
      | attributes  | [{"scope":"permissions","key":"download","value":false}] |
    And As an "user1"
    And accepting last share
    When Getting info of last share
    Then Share fields of last share match with
      | uid_owner  | user0 |
      | attributes | [{"scope":"permissions","key":"download","value":false}] |
    When creating a share with
      | path | /tmp.txt |
      | shareType   | 3 |
      | permissions | 1 |
    And Getting info of last share
    And Updating last share with
      | hideDownload | false |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    When Getting info of last share
    Then Share fields of last share match with
      | share_type     | 3     |
      | uid_owner      | user1 |
      | uid_file_owner | user0 |
      | hide_download  | 1     |
      | attributes     | [{"scope":"permissions","key":"download","value":false}] |

  Scenario: User is not allowed to reshare file with additional delete permissions
  As an "admin"
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And As an "user0"
    And creating a share with
      | path | /PARENT |
      | shareType | 0 |
      | shareWith | user1 |
      | permissions | 16 |
    And As an "user1"
    And accepting last share
    When creating a share with
      | path | /PARENT (2) |
      | shareType | 0 |
      | shareWith | user2 |
      | permissions | 25 |
    Then the OCS status code should be "404"
    And the HTTP status code should be "200"

  Scenario: User is not allowed to reshare file with additional delete permissions for files
  As an "admin"
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And As an "user0"
    And creating a share with
      | path | /textfile0.txt |
      | shareType | 0 |
      | shareWith | user1 |
      | permissions | 16 |
    And As an "user1"
    And accepting last share
    When creating a share with
      | path | /textfile0 (2).txt |
      | shareType | 0 |
      | shareWith | user2 |
      | permissions | 25 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    When Getting info of last share
    Then Share fields of last share match with
      | id | A_NUMBER |
      | item_type | file |
      | item_source | A_NUMBER |
      | share_type | 0 |
      | share_with | user2 |
      | file_source | A_NUMBER |
      | file_target | /textfile0 (2).txt |
      | path | /textfile0 (2).txt |
      | permissions | 17 |
      | stime | A_NUMBER |
      | storage | A_NUMBER |
      | mail_send | 0 |
      | uid_owner | user1 |
      | storage_id | shared::/textfile0 (2).txt |
      | file_parent | A_NUMBER |
      | share_with_displayname | user2 |
      | displayname_owner | user1 |
      | mimetype          | text/plain |

  Scenario: Get a share with a user which didn't received the share
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And file "textfile0.txt" of user "user0" is shared with user "user1"
    And As an "user2"
    When Getting info of last share
    Then the OCS status code should be "404"
    And the HTTP status code should be "200"

  Scenario: Get a share with a user with resharing rights
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And file "textfile0.txt" of user "user0" is shared with user "user1"
    And user "user1" accepts last share
    And file "textfile0.txt" of user "user0" is shared with user "user2"
    And As an "user1"
    When Getting info of last share
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Share fields of last share match with
      | id | A_NUMBER |
      | item_type | file |
      | item_source | A_NUMBER |
      | share_type | 0 |
      | share_with | user2 |
      | file_source | A_NUMBER |
      | file_target | /textfile0 (2).txt |
      | path | /textfile0 (2).txt |
      | permissions | 19 |
      | stime | A_NUMBER |
      | storage | A_NUMBER |
      | mail_send | 0 |
      | uid_owner | user0 |
      | storage_id | shared::/textfile0 (2).txt |
      | file_parent | A_NUMBER |
      | share_with_displayname | user2 |
      | displayname_owner | user0 |
      | mimetype          | text/plain |

  Scenario: Share of folder and sub-folder to same user - core#20645
    Given As an "admin"
    And user "user0" exists
    And user "user1" exists
    And group "group0" exists
    And user "user1" belongs to group "group0"
    And file "/PARENT" of user "user0" is shared with user "user1"
    And user "user1" accepts last share
    When file "/PARENT/CHILD" of user "user0" is shared with group "group0"
    And user "user1" accepts last share
    Then user "user1" should see following elements
      | /FOLDER/ |
      | /PARENT/ |
      | /PARENT/CHILD/ |
      | /PARENT/parent.txt |
      | /PARENT/CHILD/child.txt |
      | /PARENT%20(2)/ |
      | /PARENT%20(2)/CHILD/ |
      | /PARENT%20(2)/parent.txt |
      | /PARENT%20(2)/CHILD/child.txt |
      | /CHILD/ |
      | /CHILD/child.txt |
    And the HTTP status code should be "200"

  Scenario: Share a file by multiple channels
    Given As an "admin"
    And user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And group "group0" exists
    And user "user1" belongs to group "group0"
    And user "user2" belongs to group "group0"
    And user "user0" created a folder "/common"
    And user "user0" created a folder "/common/sub"
    And file "common" of user "user0" is shared with group "group0"
    And user "user1" accepts last share
    And user "user2" accepts last share
    And file "textfile0.txt" of user "user1" is shared with user "user2"
    And user "user2" accepts last share
    And User "user1" moved file "/textfile0.txt" to "/common/textfile0.txt"
    And User "user1" moved file "/common/textfile0.txt" to "/common/sub/textfile0.txt"
    And As an "user2"
    When Downloading file "/common/sub/textfile0.txt" with range "bytes=10-18"
    Then Downloaded content should be "test text"
    And Downloaded content when downloading file "/textfile0.txt" with range "bytes=10-18" should be "test text"
    And user "user2" should see following elements
      | /common/sub/textfile0.txt |

  Scenario: Share a file by multiple channels
    Given As an "admin"
    And user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And group "group0" exists
    And user "user1" belongs to group "group0"
    And user "user2" belongs to group "group0"
    And user "user0" created a folder "/common"
    And user "user0" created a folder "/common/sub"
    And file "common" of user "user0" is shared with group "group0"
    And user "user1" accepts last share
    And user "user2" accepts last share
    And file "textfile0.txt" of user "user1" is shared with user "user2"
    And user "user2" accepts last share
    And User "user1" moved file "/textfile0.txt" to "/common/textfile0.txt"
    And User "user1" moved file "/common/textfile0.txt" to "/common/sub/textfile0.txt"
    And As an "user2"
    When Downloading file "/textfile0 (2).txt" with range "bytes=10-18"
    Then Downloaded content should be "test text"
    And user "user2" should see following elements
      | /common/sub/textfile0.txt |

  Scenario: Delete all group shares
    Given As an "admin"
    And user "user0" exists
    And user "user1" exists
    And group "group1" exists
    And user "user1" belongs to group "group1"
    And file "textfile0.txt" of user "user0" is shared with group "group1"
    And user "user1" accepts last share
    And User "user1" moved file "/textfile0 (2).txt" to "/FOLDER/textfile0.txt"
    And As an "user0"
    And Deleting last share
    And As an "user1"
    When sending "GET" to "/apps/files_sharing/api/v1/shares?shared_with_me=true"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share_id is not included in the answer

  Scenario: delete a share
    Given user "user0" exists
    And user "user1" exists
    And file "textfile0.txt" of user "user0" is shared with user "user1"
    And As an "user0"
    When Deleting last share
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"

  Scenario: delete a share with a user that didn't receive the share
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And file "textfile0.txt" of user "user0" is shared with user "user1"
    And As an "user2"
    When Deleting last share
    Then the OCS status code should be "404"
    And the HTTP status code should be "200"

  Scenario: delete a share with a user with resharing rights that didn't receive the share
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And file "textfile0.txt" of user "user0" is shared with user "user1"
    And user "user1" accepts last share
    And file "textfile0.txt" of user "user0" is shared with user "user2"
    And As an "user1"
    When Deleting last share
    Then the OCS status code should be "403"
    And the HTTP status code should be "200"

  Scenario: Keep usergroup shares (#22143)
    Given As an "admin"
    And user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And group "group" exists
    And user "user1" belongs to group "group"
    And user "user2" belongs to group "group"
    And user "user0" created a folder "/TMP"
    And file "TMP" of user "user0" is shared with group "group"
    And user "user1" accepts last share
    And user "user2" accepts last share
    And user "user1" created a folder "/myFOLDER"
    And User "user1" moves file "/TMP" to "/myFOLDER/myTMP"
    And user "user2" does not exist
    And user "user1" should see following elements
      | /myFOLDER/myTMP/ |

  Scenario: Check quota of owners parent directory of a shared file
    Given using old dav path
    And As an "admin"
    And user "user0" exists
    And user "user1" exists
    And user "user1" has a quota of "0"
    And User "user0" moved file "/welcome.txt" to "/myfile.txt"
    And file "myfile.txt" of user "user0" is shared with user "user1"
    And user "user1" accepts last share
    When User "user1" uploads file "data/textfile.txt" to "/myfile.txt"
    Then the HTTP status code should be "204"

  Scenario: Don't allow sharing of the root
    Given user "user0" exists
    And As an "user0"
    When creating a share with
      | path | / |
      | shareType | 3 |
    Then the OCS status code should be "403"

  Scenario: Allow modification of reshare
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And user "user0" created a folder "/TMP"
    And file "TMP" of user "user0" is shared with user "user1"
    And user "user1" accepts last share
    And file "TMP" of user "user1" is shared with user "user2"
    And As an "user1"
    When Updating last share with
      | permissions | 1 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"

  Scenario: Allow reshare to exceed permissions if shares of same file to same user have them
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And group "group1" exists
    And user "user1" belongs to group "group1"
    And user "user0" created a folder "/TMP"
    And As an "user0"
    And creating a share with
      | path | /TMP |
      | shareType | 1 |
      | shareWith | group1 |
      | permissions | 15 |
    And As an "user1"
    And accepting last share
    And As an "user0"
    And creating a share with
      | path | /TMP |
      | shareType | 0 |
      | shareWith | user1 |
      | permissions | 17 |
    And As an "user1"
    And accepting last share
    And creating a share with
      | path | /TMP |
      | shareType | 0 |
      | shareWith | user2 |
      | permissions | 17 |
    When Updating last share with
      | permissions | 31 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"

  Scenario: Do not allow reshare to exceed permissions
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And user "user0" created a folder "/TMP"
    And As an "user0"
    And creating a share with
      | path | /TMP |
      | shareType | 0 |
      | shareWith | user1 |
      | permissions | 21 |
    And As an "user1"
    And accepting last share
    And creating a share with
      | path | /TMP |
      | shareType | 0 |
      | shareWith | user2 |
      | permissions | 21 |
    When Updating last share with
      | permissions | 31 |
    Then the OCS status code should be "404"
    And the HTTP status code should be "200"

  Scenario: Do not allow reshare to exceed permissions even if shares of same file to other users have them
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And user "user3" exists
    And user "user0" created a folder "/TMP"
    And As an "user0"
    And creating a share with
      | path | /TMP |
      | shareType | 0 |
      | shareWith | user3 |
      | permissions | 15 |
    And As an "user3"
    And accepting last share
    And As an "user0"
    And creating a share with
      | path | /TMP |
      | shareType | 0 |
      | shareWith | user1 |
      | permissions | 21 |
    And As an "user1"
    And accepting last share
    And creating a share with
      | path | /TMP |
      | shareType | 0 |
      | shareWith | user2 |
      | permissions | 21 |
    When Updating last share with
      | permissions | 31 |
    Then the OCS status code should be "404"
    And the HTTP status code should be "200"

  Scenario: Do not allow reshare to exceed permissions even if shares of other files from same user have them
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And As an "user0"
    And creating a share with
      | path | /FOLDER |
      | shareType | 0 |
      | shareWith | user1 |
      | permissions | 15 |
    And As an "user1"
    And accepting last share
    And user "user0" created a folder "/TMP"
    And As an "user0"
    And creating a share with
      | path | /TMP |
      | shareType | 0 |
      | shareWith | user1 |
      | permissions | 21 |
    And As an "user1"
    And accepting last share
    And creating a share with
      | path | /TMP |
      | shareType | 0 |
      | shareWith | user2 |
      | permissions | 21 |
    When Updating last share with
      | permissions | 31 |
    Then the OCS status code should be "404"
    And the HTTP status code should be "200"

  Scenario: Do not allow reshare to exceed permissions even if shares of other files from other users have them
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And user "user3" exists
    And As an "user3"
    And creating a share with
      | path | /FOLDER |
      | shareType | 0 |
      | shareWith | user1 |
      | permissions | 15 |
    And As an "user1"
    And accepting last share
    And user "user0" created a folder "/TMP"
    And As an "user0"
    And creating a share with
      | path | /TMP |
      | shareType | 0 |
      | shareWith | user1 |
      | permissions | 21 |
    And As an "user1"
    And accepting last share
    And creating a share with
      | path | /TMP |
      | shareType | 0 |
      | shareWith | user2 |
      | permissions | 21 |
    When Updating last share with
      | permissions | 31 |
    Then the OCS status code should be "404"
    And the HTTP status code should be "200"

  Scenario: Do not allow sub reshare to exceed permissions
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And user "user0" created a folder "/TMP"
    And user "user0" created a folder "/TMP/SUB"
    And As an "user0"
    And creating a share with
      | path | /TMP |
      | shareType | 0 |
      | shareWith | user1 |
      | permissions | 21 |
    And As an "user1"
    And accepting last share
    And creating a share with
      | path | /TMP/SUB |
      | shareType | 0 |
      | shareWith | user2 |
      | permissions | 21 |
    When Updating last share with
      | permissions | 31 |
    Then the OCS status code should be "404"
    And the HTTP status code should be "200"

  Scenario: Only allow 1 link share per file/folder
    Given user "user0" exists
    And As an "user0"
    And creating a share with
      | path | welcome.txt |
      | shareType | 3 |
    When save last share id
    And creating a share with
      | path | welcome.txt |
      | shareType | 3      |
    Then share ids should match

  Scenario: Correct webdav share-permissions for owned file
    Given user "user0" exists
    And User "user0" uploads file with content "foo" to "/tmp.txt"
    When as "user0" gets properties of folder "/tmp.txt" with
      |{http://open-collaboration-services.org/ns}share-permissions |
    Then the single response should contain a property "{http://open-collaboration-services.org/ns}share-permissions" with value "19"

  Scenario: Cannot download a file when it's shared view-only without shareapi_allow_view_without_download
    Given As an "admin"
    And parameter "shareapi_allow_view_without_download" of app "core" is set to "no"
    Given user "user0" exists
    And user "user1" exists
    And User "user0" moves file "/textfile0.txt" to "/document.odt"
    And file "document.odt" of user "user0" is shared with user "user1" view-only
    And user "user1" accepts last share
    When As an "user1"
    And Downloading file "/document.odt"
    Then the HTTP status code should be "403"
    Then As an "admin"
    And parameter "shareapi_allow_view_without_download" of app "core" is set to "yes"
    Then As an "user1"
    And Downloading file "/document.odt"
    Then the HTTP status code should be "200"

  Scenario: Cannot download a file when its parent is shared view-only without shareapi_allow_view_without_download
    Given As an "admin"
    And parameter "shareapi_allow_view_without_download" of app "core" is set to "no"
    Given user "user0" exists
    And user "user1" exists
    And User "user0" created a folder "/sharedviewonly"
    And User "user0" moves file "/textfile0.txt" to "/sharedviewonly/document.odt"
    And folder "sharedviewonly" of user "user0" is shared with user "user1" view-only
    And user "user1" accepts last share
    When As an "user1"
    And Downloading file "/sharedviewonly/document.odt"
    Then the HTTP status code should be "403"
    Then As an "admin"
    And parameter "shareapi_allow_view_without_download" of app "core" is set to "yes"
    Then As an "user1"
    And Downloading file "/sharedviewonly/document.odt"
    Then the HTTP status code should be "200"

  Scenario: Cannot copy a file when it's shared view-only even with shareapi_allow_view_without_download enabled
    Given As an "admin"
    And parameter "shareapi_allow_view_without_download" of app "core" is set to "no"
    Given user "user0" exists
    And user "user1" exists
    And User "user0" moves file "/textfile0.txt" to "/document.odt"
    And file "document.odt" of user "user0" is shared with user "user1" view-only
    And user "user1" accepts last share
    When User "user1" copies file "/document.odt" to "/copyforbidden.odt"
    Then the HTTP status code should be "403"
    Then As an "admin"
    And parameter "shareapi_allow_view_without_download" of app "core" is set to "yes"
    Then As an "user1"
    And User "user1" copies file "/document.odt" to "/copyforbidden.odt"
    Then the HTTP status code should be "403"

  Scenario: Cannot copy a file when its parent is shared view-only
    Given As an "admin"
    And parameter "shareapi_allow_view_without_download" of app "core" is set to "no"
    Given user "user0" exists
    And user "user1" exists
    And User "user0" created a folder "/sharedviewonly"
    And User "user0" moves file "/textfile0.txt" to "/sharedviewonly/document.odt"
    And folder "sharedviewonly" of user "user0" is shared with user "user1" view-only
    And user "user1" accepts last share
    When User "user1" copies file "/sharedviewonly/document.odt" to "/copyforbidden.odt"
    Then the HTTP status code should be "403"
    Then As an "admin"
    And parameter "shareapi_allow_view_without_download" of app "core" is set to "yes"
    Then As an "user1"
    And User "user1" copies file "/sharedviewonly/document.odt" to "/copyforbidden.odt"
    Then the HTTP status code should be "403"

# See sharing-v1-part3.feature
