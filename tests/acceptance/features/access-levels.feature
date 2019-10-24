Feature: access-levels

  Scenario: regular users cannot see admin-level items in the Settings menu
    Given I am logged in
    When I open the Settings menu
    Then I see that the Settings menu is shown
    And I see that the "Settings" item in the Settings menu is shown
    And I see that the "Users" item in the Settings menu is not shown
    And I see that the "Help" item in the Settings menu is shown
    And I see that the "Log out" item in the Settings menu is shown

  Scenario: regular users cannot see admin-level items on the Settings page
    Given I am logged in
    When I visit the settings page
    Then I see that the "Personal info" entry in the settings panel is shown
    And I see that the "Personal" settings panel is not shown
    And I see that the "Administration" settings panel is not shown

  Scenario: admin users can see admin-level items on the Settings page
    Given I am logged in as the admin
    When I visit the settings page
    Then I see that the "Personal" settings panel is shown
    And I see that the "Administration" settings panel is shown
