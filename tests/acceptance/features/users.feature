@apache
Feature: users

  Scenario: assign user to a group
    Given I act as Jane
    And I am logged in as the admin
    And I open the User settings
    # And I see that the list of users contains the user user0
    # When I toggle the edit mode for the user user0
    # Then I see that the edit mode is on for user user0
    # disabled because we need the TAB patch:
    # https://github.com/minkphp/MinkSelenium2Driver/pull/244
    # When I assign the user user0 to the group admin
    # Then I see that the section Admins is shown
    # And I see that the section Admins has a count of 2

  Scenario: create and delete a group
    Given I act as Jane
    And I am logged in as the admin
    And I open the User settings
    # And I see that the list of users contains the user user0
    # disabled because we need the TAB patch:
    # https://github.com/minkphp/MinkSelenium2Driver/pull/244
    # And I assign the user user0 to the group Group1
    # And I see that the section Group1 is shown
    # And I click the "icon-delete" button on the Group1 section
    # And I see that the confirmation dialog is shown
    # When I click the "Yes" button of the confirmation dialog
    # Then I see that the section Group1 is not shown

  Scenario: delete an empty group
    Given I act as Jane
    And I am logged in as the admin
    And I open the User settings
    # disabled because we need the TAB patch:
    # https://github.com/minkphp/MinkSelenium2Driver/pull/244
    # And I assign the user user0 to the group Group1
    # And I see that the section Group1 is shown
    # And I withdraw the user user0 from the group Group1
    # And I see that the section Group1 does not have a count
    # And I click the "icon-delete" button on the Group1 section
    # And I see that the confirmation dialog is shown
    # When I click the "Yes" button of the confirmation dialog
    # Then I see that the section Group1 is not shown

#  Scenario: change email
#    Given I act as Jane
#    And I am logged in as the admin
#    And I open the User settings
#    And I see that the list of users contains the user user0
#    And I see that the mailAddress of user0 is ""
#    When I set the mailAddress for user0 to "test@nextcloud.com"
#    And I see that the mailAddress cell for user user0 is done loading
#    Then I see that the mailAddress of user0 is "test@nextcloud.com"

  Scenario: change user quota
    Given I act as Jane
    And I am logged in as the admin
    And I open the User settings
    # And I see that the list of users contains the user user0
    # When I toggle the edit mode for the user user0
    # Then I see that the edit mode is on for user user0
    # And I see that the user quota of user0 is Unlimited
    # disabled because we need the TAB patch:
    # https://github.com/minkphp/MinkSelenium2Driver/pull/244
    # When I set the user user0 quota to 1GB
    # And I see that the quota cell for user user0 is done loading
    # Then I see that the user quota of user0 is "1 GB"
    # When I set the user user0 quota to Unlimited
    # And I see that the quota cell for user user0 is done loading
    # Then I see that the user quota of user0 is Unlimited
    # When I set the user user0 quota to 0
    # And I see that the quota cell for user user0 is done loading
    # Then I see that the user quota of user0 is "0 B"
    # When I set the user user0 quota to Default
    # And I see that the quota cell for user user0 is done loading
    # Then I see that the user quota of user0 is "Default quota"
