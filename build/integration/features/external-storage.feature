Feature: external-storage
  Background:
    Given using api version "1"
    Given using dav path "remote.php/webdav"

  @local_storage
  Scenario: Share by link a file inside a local external storage
    Given user "user0" exists
    And user "user1" exists
    And As an "user0"
    And user "user0" created a folder "/local_storage/foo"
    And User "user0" moved file "/textfile0.txt" to "/local_storage/foo/textfile0.txt"
    And folder "/local_storage/foo" of user "user0" is shared with user "user1"
    And As an "user1"
    When creating a share with
      | path | foo |
      | shareType | 3 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Share fields of last share match with
      | id | A_NUMBER |
      | url | AN_URL |
      | token | A_TOKEN |
      | mimetype | httpd/unix-directory |


