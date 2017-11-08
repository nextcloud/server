Feature: app-comments

  Scenario: Writing a comment
    Given I am logged in
    And I open the details view for "welcome.txt"
    And I open the "Comments" tab in the details view
    When I create a new comment with "Hello world" as message
    Then I see that a comment was added
