Feature: Hidden folder
  Background:
    Given using api version "1"
    And user "user0" exists
    And system parameter "instanceid" is set to "dummy"
    And User "user0" created a folder "/.hidden_instance"
    And system parameter "instanceid" is set to "instance"

  Scenario: The hidden folder should not be listed in the root
    When User "user0" created a folder "/folder"
    Then user "user0" should see following elements
      | /folder/ |
    And user "user0" should not see following elements
      | /.hidden_instance/ |

  Scenario: Folders can be created inside the hidden folder
    When User "user0" created a folder "/.hidden_instance/sub"
    Then the HTTP status code should be "201"
    And user "user0" should see following elements in folder "/.hidden_instance/"
      | /.hidden_instance/sub/ |

  Scenario: Trying to delete the hidden folder should fail
    Given User "user0" deletes folder "/.hidden_instance"
    Then the HTTP status code should be "403"

  Scenario: Trying to rename the hidden folder should fail
    Given User "user0" moves folder "/.hidden_instance" to "/foo"
    Then the HTTP status code should be "403"

  Scenario: Trying to overwrite the hidden folder with a rename should fail
    When User "user0" created a folder "/folder"
    And User "user0" moves folder "/folder" to "/.hidden_instance"
    Then the HTTP status code should be "403"
