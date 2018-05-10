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
    And I see that the "Delete user" action in the user0 actions menu is shown
    When I click the "Delete user" action in the user0 actions menu
    Then I see that the list of users does not contains the user user0

  Scenario: disable a user
    Given I act as Jane
    And I am logged in as the admin
    And I open the User settings
    And I see that the list of users contains the user user0
    And I open the actions menu for the user user0
    And I see that the "Disable user" action in the user0 actions menu is shown
    When I click the "Disable user" action in the user0 actions menu
    Then I see that the list of users does not contains the user user0
    When I open the "Disabled users" section
    Then I see that the list of users contains the user user0

  Scenario: assign user to a group
    Given I act as Jane
    And I am logged in as the admin
    And I open the User settings
    And I see that the list of users contains the user user0
    # disabled because we need the TAB patch: 
    # https://github.com/minkphp/MinkSelenium2Driver/pull/244
    # When I assign the user user0 to the group admin
    # Then I see that the section Admins is shown
    # And I see that the section Admins has a count of 2
  
  Scenario: create and delete a group
    Given I act as Jane
    And I am logged in as the admin
    And I open the User settings
    And I see that the list of users contains the user user0
    # disabled because we need the TAB patch: 
    # https://github.com/minkphp/MinkSelenium2Driver/pull/244
    # And I assign the user user0 to the group Group1
    # And I see that the section Group1 is shown
    # And I click the "icon-delete" button on the Group1 section
    # And I see that the confirmation dialog is shown
    # When I click the "Yes" button of the confirmation dialog
    # Then I see that the section Group1 is not shown

  Scenario: change columns visibility
    Given I act as Jane
    And I am logged in as the admin
    And I open the User settings
    And I open the settings
    And I see that the settings are opened
    When I toggle the showLanguages checkbox in the settings
    Then I see that the "Languages" column is shown
    When I toggle the showLastLogin checkbox in the settings
    Then I see that the "Last login" column is shown
    When I toggle the showStoragePath checkbox in the settings
    Then I see that the "Storage location" column is shown
    When I toggle the showUserBackend checkbox in the settings
    Then I see that the "User backend" column is shown
    
