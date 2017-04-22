Feature: app-files

  Scenario: set a password to a shared link
    Given I am logged in
    And I share the link for "welcome.txt"
    When I protect the shared link with the password "abcdef"
    Then I see that the working icon for password protect is shown
    And I see that the working icon for password protect is eventually not shown

  Scenario: access a shared link protected by password with a valid password
    Given I act as John
    And I am logged in
    And I share the link for "welcome.txt" protected by the password "abcdef"
    And I write down the shared link
    When I act as Jane
    And I visit the shared link I wrote down
    And I see that the current page is the Authenticate page for the shared link I wrote down
    And I authenticate with password "abcdef"
    Then I see that the current page is the shared link I wrote down
    And I see that the shared file preview shows the text "Welcome to your Nextcloud account!"

  Scenario: access a shared link protected by password with an invalid password
    Given I act as John
    And I am logged in
    And I share the link for "welcome.txt" protected by the password "abcdef"
    And I write down the shared link
    When I act as Jane
    And I visit the shared link I wrote down
    And I authenticate with password "fedcba"
    Then I see that the current page is the Authenticate page for the shared link I wrote down
    And I see that a wrong password for the shared file message is shown
