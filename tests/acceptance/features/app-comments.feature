Feature: app-comments

  Scenario: Writing a comment
    Given I am logged in
    And I open the details view for "welcome.txt"
    And I open the "Comments" tab in the details view
    When I create a new comment with "Hello world" as message
    Then I see a comment with "Hello world" as message

  Scenario: open the comments for a different file
    Given I am logged in
    And I create a new folder named "Folder"
    And I open the details view for "welcome.txt"
    And I open the "Comments" tab in the details view
    And I create a new comment with "Hello world" as message
    And I see a comment with "Hello world" as message
    When I open the details view for "Folder"
    # The "Comments" tab should already be opened
    Then I see that there are no comments

  Scenario: write a comment in a file right after writing a comment in another file
    Given I am logged in
    And I create a new folder named "Folder"
    And I open the details view for "Folder"
    And I open the "Comments" tab in the details view
    And I create a new comment with "Comment in Folder" as message
    And I open the details view for "welcome.txt"
    # The "Comments" tab should already be opened
    When I create a new comment with "Comment in welcome.txt" as message
    Then I see a comment with "Comment in welcome.txt" as message
    And I see that there is no comment with "Comment in Folder" as message
