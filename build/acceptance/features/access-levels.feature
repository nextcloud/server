Feature: access-levels

  Scenario: regular users can not see admin-level items in the Settings menu
    Given I am logged in
    When I open the Settings menu
    Then I see that the Settings menu is shown
    And I see that the "Personal" item in the Settings menu is shown
    And I see that the "Admin" item in the Settings menu is not shown
    And I see that the "Users" item in the Settings menu is not shown
    And I see that the "Help" item in the Settings menu is shown
    And I see that the "Log out" item in the Settings menu is shown

  Scenario: admin users can see admin-level items in the Settings menu
    Given I am logged in as the admin
    When I open the Settings menu
    Then I see that the Settings menu is shown
    And I see that the "Personal" item in the Settings menu is shown
    And I see that the "Admin" item in the Settings menu is shown
    And I see that the "Users" item in the Settings menu is shown
    And I see that the "Help" item in the Settings menu is shown
    And I see that the "Log out" item in the Settings menu is shown
