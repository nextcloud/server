@apache
Feature: login

  Scenario: log in with valid user and password
    Given I visit the Home page
    When I log in with user user0 and password 123456acb
    Then I see that the current page is the Files app

  Scenario: try to log in with valid user and invalid password
    Given I visit the Home page
    When I log in with user user0 and password 654321
    Then I see that the current page is the Login page
    And I see that a wrong password message is shown

#  Scenario: log in with valid user and invalid password once fixed by admin
#    Given I act as John
#    And I can not log in with user user0 and password 654231
#    When I act as Jane
#    And I am logged in as the admin
#    And I open the User settings
#    And I set the password for user0 to 654321
#    And I act as John
#    And I log in with user user0 and password 654321
#    Then I see that the current page is the Files app

  Scenario: try to log in with invalid user
    Given I visit the Home page
    When I log in with user unknownUser and password 123456acb
    Then I see that the current page is the Login page
    And I see that a wrong password message is shown

  Scenario: try to log in as disabled user
    Given I visit the Home page
    When I log in with user disabledUser and password 123456acb
    Then I see that the current page is the Login page
    And I see that the disabled user message is shown

  Scenario: log in with invalid user once fixed by admin
    Given I act as John
    And I can not log in with user unknownUser and password 123456acb
    When I act as Jane
    And I am logged in as the admin
    And I open the User settings
    And I click the New user button
    And I see that the new user form is shown
    And I create user unknownUser with password 123456acb
    # And I see that the list of users contains the user unknownUser
    And I act as John
    And I log in with user unknownUser and password 123456acb
    Then I see that the current page is the Files app

  Scenario: log out
    Given I am logged in
    When I log out
    Then I see that the current page is the Login page
