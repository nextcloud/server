@apache
Feature: header

  Scenario: other users are seen in the contacts menu
    Given I am logged in as the admin
    When I open the Contacts menu
    Then I see that the Contacts menu is shown
    And I see that the contact "user0" in the Contacts menu is shown
    And I see that the contact "admin" in the Contacts menu is not shown

#  Scenario: users from other groups are not seen in the contacts menu when autocompletion is restricted within the same group
#    Given I am logged in as the admin
#    And I visit the admin settings page
#    And I open the "Sharing" section of the "Administration" group
#    And I enable restricting username autocompletion to groups
#    And I see that username autocompletion is restricted to groups
#    When I open the Contacts menu
#    Then I see that the Contacts menu is shown
#    And I see that the contact "user0" in the Contacts menu is not shown
#    And I see that the contact "admin" in the Contacts menu is not shown

  Scenario: just added users are seen in the contacts menu
    Given I am logged in as the admin
    And I open the User settings
    And I click the New user button
    And I see that the new user form is shown
    And I create user user2 with password 123456acb
    # And I see that the list of users contains the user user2
    When I open the Contacts menu
    Then I see that the Contacts menu is shown
    And I see that the contact "user0" in the Contacts menu is shown
    And I see that the contact "user1" in the Contacts menu is shown
    And I see that the contact "user2" in the Contacts menu is shown
    And I see that the contact "admin" in the Contacts menu is not shown

  Scenario: search for other users in the contacts menu
    Given I am logged in as the admin
    And I open the Contacts menu
    And I see that the Contacts menu is shown
    And I see that the contact "user0" in the Contacts menu is shown
    And I see that the contact "user1" in the Contacts menu is shown
    And I see that the Contacts menu search input is shown
    When I search for the user "user0"
    # First check that "user1" is no longer shown to ensure that the search was
    # made; checking that "user0" is shown or that "admin" is not shown does not
    # guarantee that (as they were already being shown and not being shown,
    # respectively, before the search started).
    Then I see that the contact "user1" in the Contacts menu is eventually not shown
    And I see that the contact "user0" in the Contacts menu is shown
    And I see that the contact "admin" in the Contacts menu is not shown

  Scenario: search for unknown users in the contacts menu
    Given I am logged in as the admin
    And I open the Contacts menu
    And I see that the Contacts menu is shown
    And I see that the contact "user0" in the Contacts menu is shown
    And I see that the Contacts menu search input is shown
    When I search for the user "unknownuser"
    Then I see that the no results message in the Contacts menu is shown
    And I see that the contact "user0" in the Contacts menu is not shown
    And I see that the contact "admin" in the Contacts menu is not shown
