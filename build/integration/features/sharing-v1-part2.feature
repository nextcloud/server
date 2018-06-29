Feature: sharing
  Background:
    Given using api version "1"
    Given using old dav path

# See sharing-v1.feature

  Scenario: getting all shares of a file with reshares
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And user "user3" exists
    And file "textfile0.txt" of user "user0" is shared with user "user1"
    And file "textfile0 (2).txt" of user "user1" is shared with user "user2"
    And As an "user0"
    When sending "GET" to "/apps/files_sharing/api/v1/shares?reshares=true&path=textfile0.txt"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And User "user1" should be included in the response
    And User "user2" should be included in the response
    And User "user3" should not be included in the response

  Scenario: Reshared files can be still accessed if a user in the middle removes it.
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And user "user3" exists
    And file "textfile0.txt" of user "user0" is shared with user "user1"
    And file "textfile0 (2).txt" of user "user1" is shared with user "user2"
    And file "textfile0 (2).txt" of user "user2" is shared with user "user3"
    And As an "user1"
    When User "user1" deletes file "/textfile0 (2).txt"
    And As an "user3"
    And Downloading file "/textfile0 (2).txt" with range "bytes=1-8"
    Then Downloaded content should be "extcloud"

  Scenario: getting share info of a share
    Given user "user0" exists
    And user "user1" exists
    And file "textfile0.txt" of user "user0" is shared with user "user1"
    And As an "user0"
    When Getting info of last share
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Share fields of last share match with
      | id | A_NUMBER |
      | item_type | file |
      | item_source | A_NUMBER |
      | share_type | 0 |
      | share_with | user1 |
      | file_source | A_NUMBER |
      | file_target | /textfile0.txt |
      | path | /textfile0.txt |
      | permissions | 19 |
      | stime | A_NUMBER |
      | storage | A_NUMBER |
      | mail_send | 0 |
      | uid_owner | user0 |
      | storage_id | home::user0 |
      | file_parent | A_NUMBER |
      | share_with_displayname | user1 |
      | displayname_owner | user0 |
      | mimetype          | text/plain |

  Scenario: getting share info of a group share
    Given user "user0" exists
    And user "user1" exists
    And group "group1" exists
    And user "user1" belongs to group "group1"
    And file "textfile0.txt" of user "user0" is shared with group "group1"
    And As an "user0"
    When Getting info of last share
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Share fields of last share match with
      | id | A_NUMBER |
      | item_type | file |
      | item_source | A_NUMBER |
      | share_type | 1 |
      | share_with | group1 |
      | file_source | A_NUMBER |
      | file_target | /textfile0.txt |
      | path | /textfile0.txt |
      | permissions | 19 |
      | stime | A_NUMBER |
      | storage | A_NUMBER |
      | mail_send | 0 |
      | uid_owner | user0 |
      | storage_id | home::user0 |
      | file_parent | A_NUMBER |
      | share_with_displayname | group1 |
      | displayname_owner | user0 |
      | mimetype          | text/plain |
    And As an "user1"
    And Getting info of last share
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Share fields of last share match with
      | id | A_NUMBER |
      | item_type | file |
      | item_source | A_NUMBER |
      | share_type | 1 |
      | share_with | group1 |
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
      | share_with_displayname | group1 |
      | displayname_owner | user0 |
      | mimetype          | text/plain |

  Scenario: keep group permissions in sync
    Given As an "admin"
    Given user "user0" exists
    And user "user1" exists
    And group "group1" exists
    And user "user1" belongs to group "group1"
    And file "textfile0.txt" of user "user0" is shared with group "group1"
    And User "user1" moved file "/textfile0 (2).txt" to "/FOLDER/textfile0.txt"
    And As an "user0"
    When Updating last share with
      | permissions | 1 |
    And Getting info of last share
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Share fields of last share match with
      | id | A_NUMBER |
      | item_type | file |
      | item_source | A_NUMBER |
      | share_type | 1 |
      | file_source | A_NUMBER |
      | file_target | /textfile0.txt |
      | permissions | 1 |
      | stime | A_NUMBER |
      | storage | A_NUMBER |
      | mail_send | 0 |
      | uid_owner | user0 |
      | storage_id | home::user0 |
      | file_parent | A_NUMBER |
      | displayname_owner | user0 |
      | mimetype          | text/plain |
    And As an "user1"
    And Getting info of last share
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Share fields of last share match with
      | id | A_NUMBER |
      | item_type | file |
      | item_source | A_NUMBER |
      | share_type | 1 |
      | file_source | A_NUMBER |
      | file_target | /FOLDER/textfile0.txt |
      | permissions | 1 |
      | stime | A_NUMBER |
      | storage | A_NUMBER |
      | mail_send | 0 |
      | uid_owner | user0 |
      | storage_id | shared::/FOLDER/textfile0.txt |
      | file_parent | A_NUMBER |
      | displayname_owner | user0 |
      | mimetype          | text/plain |

  Scenario: Sharee can see the share
    Given user "user0" exists
    And user "user1" exists
    And file "textfile0.txt" of user "user0" is shared with user "user1"
    And As an "user1"
    When sending "GET" to "/apps/files_sharing/api/v1/shares?shared_with_me=true"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share_id is included in the answer

  Scenario: Sharee can see the filtered share
    Given user "user0" exists
    And user "user1" exists
    And file "textfile0.txt" of user "user0" is shared with user "user1"
    And file "textfile1.txt" of user "user0" is shared with user "user1"
    And As an "user1"
    When sending "GET" to "/apps/files_sharing/api/v1/shares?shared_with_me=true&path=textfile1 (2).txt"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share_id is included in the answer

  Scenario: Sharee can't see the share that is filtered out
    Given user "user0" exists
    And user "user1" exists
    And file "textfile0.txt" of user "user0" is shared with user "user1"
    And file "textfile1.txt" of user "user0" is shared with user "user1"
    And As an "user1"
    When sending "GET" to "/apps/files_sharing/api/v1/shares?shared_with_me=true&path=textfile0 (2).txt"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share_id is not included in the answer

  Scenario: Sharee can see the group share
    Given As an "admin"
    And user "user0" exists
    And user "user1" exists
    And group "group0" exists
    And user "user1" belongs to group "group0"
    And file "textfile0.txt" of user "user0" is shared with group "group0"
    And As an "user1"
    When sending "GET" to "/apps/files_sharing/api/v1/shares?shared_with_me=true"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And last share_id is included in the answer

  Scenario: User is not allowed to reshare file
  As an "admin"
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And As an "user0"
    And creating a share with
      | path | /textfile0.txt |
      | shareType | 0 |
      | shareWith | user1 |
      | permissions | 8 |
    And As an "user1"
    When creating a share with
      | path | /textfile0 (2).txt |
      | shareType | 0 |
      | shareWith | user2 |
      | permissions | 31 |
    Then the OCS status code should be "404"
    And the HTTP status code should be "200"

  Scenario: User is not allowed to reshare file with more permissions
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
    When creating a share with
      | path | /textfile0 (2).txt |
      | shareType | 0 |
      | shareWith | user2 |
      | permissions | 31 |
    Then the OCS status code should be "404"
    And the HTTP status code should be "200"

  Scenario: Get a share with a user which didn't received the share
    Given user "user0" exists
    And user "user1" exists
    And user "user2" exists
    And file "textfile0.txt" of user "user0" is shared with user "user1"
    And As an "user2"
    When Getting info of last share
    Then the OCS status code should be "404"
    And the HTTP status code should be "200"

  Scenario: Share of folder and sub-folder to same user - core#20645
    Given As an "admin"
    And user "user0" exists
    And user "user1" exists
    And group "group0" exists
    And user "user1" belongs to group "group0"
    And file "/PARENT" of user "user0" is shared with user "user1"
    When file "/PARENT/CHILD" of user "user0" is shared with group "group0"
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
    And file "textfile0.txt" of user "user1" is shared with user "user2"
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
    And file "textfile0.txt" of user "user1" is shared with user "user2"
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
    And file "TMP" of user "user1" is shared with user "user2"
    And As an "user1"
    When Updating last share with
      | permissions | 1 |
    Then the OCS status code should be "100"

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
    And creating a share with
      | path | /TMP |
      | shareType | 0 |
      | shareWith | user2 |
      | permissions | 21 |
    When Updating last share with
      | permissions | 31 |
    Then the OCS status code should be "404"

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

# See sharing-v1-part3.feature
