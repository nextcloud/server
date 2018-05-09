Feature: users

  Scenario: create a new user
    Given I act as Jane
    And I am logged in as the admin
    And I open the User settings
    And I click the New user button
    And I see that the new user form is shown
    When I create user unknownUser with password 123456acb
    Then I see that the list of users contains the user unknownUser

  Scenario: delete a user
    Given I act as Jane
    And I am logged in as the admin
    And I open the User settings
    And I see that the list of users contains the user user0
    And I open the actions menu for the user user0
    And I see the "Delete user" action in the user0 actions menu
    When I click the "Delete user" action in the user0 actions menu
    Then I see that the list of users does not contains the user user0

