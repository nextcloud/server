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
    And I see a comment with "Comment in Folder" as message
    And I open the details view for "welcome.txt"
    # The "Comments" tab should already be opened
    When I create a new comment with "Comment in welcome.txt" as message
    Then I see a comment with "Comment in welcome.txt" as message
    And I see that there is no comment with "Comment in Folder" as message



  Scenario: read a comment written by the sharer
    Given I act as John
    And I am logged in as the admin
    And I act as Jane
    And I am logged in
    And I act as John
    And I rename "welcome.txt" to "shared.txt"
    And I share "shared.txt" with "user0"
    And I see that the file is shared with "user0"
    # The details view should already be open
    And I open the "Comments" tab in the details view
    And I create a new comment with "Hello world" as message
    And I see a comment with "Hello world" as message
    When I act as Jane
    # The Files app is open again to reload the file list and the comments
    And I open the Files app
    And I open the details view for "shared.txt"
    And I open the "Comments" tab in the details view
    Then I see a comment with "Hello world" as message

  Scenario: read a comment written by the sharee
    Given I act as John
    And I am logged in as the admin
    And I act as Jane
    And I am logged in
    And I act as John
    And I rename "welcome.txt" to "shared.txt"
    And I share "shared.txt" with "user0"
    And I see that the file is shared with "user0"
    And I act as Jane
    # The Files app is open again to reload the file list
    And I open the Files app
    And I open the details view for "shared.txt"
    And I open the "Comments" tab in the details view
    And I create a new comment with "Hello world" as message
    And I see a comment with "Hello world" as message
    When I act as John
    # The Files app is open again to reload the file list and the comments
    And I open the Files app
    And I open the details view for "shared.txt"
    And I open the "Comments" tab in the details view
    Then I see a comment with "Hello world" as message



  Scenario: unread comment icon shown for comment written by the sharer in a shared file
    Given I act as John
    And I am logged in as the admin
    And I act as Jane
    And I am logged in
    And I act as John
    And I rename "welcome.txt" to "shared.txt"
    And I share "shared.txt" with "user0"
    And I see that the file is shared with "user0"
    # The details view should already be open
    And I open the "Comments" tab in the details view
    And I create a new comment with "Hello world" as message
    And I see a comment with "Hello world" as message
    When I act as Jane
    # The Files app is open again to reload the file list and the comments
    And I open the Files app
    Then I see that "shared.txt" has unread comments
    And I open the unread comments for "shared.txt"
    And I see that the details view is open
    And I see a comment with "Hello world" as message

  Scenario: unread comment icon shown for comment written by the sharee in a shared file
    Given I act as John
    And I am logged in as the admin
    And I act as Jane
    And I am logged in
    And I act as John
    And I rename "welcome.txt" to "shared.txt"
    And I share "shared.txt" with "user0"
    And I see that the file is shared with "user0"
    And I act as Jane
    # The Files app is open again to reload the file list
    And I open the Files app
    And I open the details view for "shared.txt"
    And I open the "Comments" tab in the details view
    And I create a new comment with "Hello world" as message
    And I see a comment with "Hello world" as message
    When I act as John
    # The Files app is open again to reload the file list and the comments
    And I open the Files app
    Then I see that "shared.txt" has unread comments
    And I open the unread comments for "shared.txt"
    And I see that the details view is open
    And I see a comment with "Hello world" as message

  Scenario: unread comment icon shown for comment written by the sharer in a shared folder
    Given I act as John
    And I am logged in as the admin
    And I act as Jane
    And I am logged in
    And I act as John
    And I create a new folder named "Folder"
    And I share "Folder" with "user0"
    And I see that the file is shared with "user0"
    # The details view should already be open
    And I open the "Comments" tab in the details view
    And I create a new comment with "Hello world" as message
    And I see a comment with "Hello world" as message
    When I act as Jane
    # The Files app is open again to reload the file list and the comments
    And I open the Files app
    Then I see that "Folder" has unread comments
    And I open the unread comments for "Folder"
    And I see that the details view is open
    And I see a comment with "Hello world" as message

  Scenario: unread comment icon shown for comment written by the sharee in a shared folder
    Given I act as John
    And I am logged in as the admin
    And I act as Jane
    And I am logged in
    And I act as John
    And I create a new folder named "Folder"
    And I share "Folder" with "user0"
    And I see that the file is shared with "user0"
    And I act as Jane
    # The Files app is open again to reload the file list
    And I open the Files app
    And I open the details view for "Folder"
    And I open the "Comments" tab in the details view
    And I create a new comment with "Hello world" as message
    And I see a comment with "Hello world" as message
    When I act as John
    # The Files app is open again to reload the file list and the comments
    And I open the Files app
    Then I see that "Folder" has unread comments
    And I open the unread comments for "Folder"
    And I see that the details view is open
    And I see a comment with "Hello world" as message

  Scenario: unread comment icon shown for comment written by the sharer in a child folder of a shared folder
    Given I act as John
    And I am logged in as the admin
    And I act as Jane
    And I am logged in
    And I act as John
    And I create a new folder named "Folder"
    And I share "Folder" with "user0"
    And I see that the file is shared with "user0"
    And I enter in the folder named "Folder"
    And I create a new folder named "Child folder"
    # The details view should already be open
    And I open the "Comments" tab in the details view
    And I create a new comment with "Hello world" as message
    And I see a comment with "Hello world" as message
    When I act as Jane
    # The Files app is open again to reload the file list and the comments
    And I open the Files app
    And I enter in the folder named "Folder"
    Then I see that "Child folder" has unread comments
    And I open the unread comments for "Child folder"
    And I see that the details view is open
    And I see a comment with "Hello world" as message

  Scenario: unread comment icon shown for comment written by the sharee in a child folder of a shared folder
    Given I act as John
    And I am logged in as the admin
    And I act as Jane
    And I am logged in
    And I act as John
    And I create a new folder named "Folder"
    And I share "Folder" with "user0"
    And I see that the file is shared with "user0"
    And I act as Jane
    # The Files app is open again to reload the file list
    And I open the Files app
    And I enter in the folder named "Folder"
    And I create a new folder named "Child folder"
    # The details view should already be open
    And I open the "Comments" tab in the details view
    And I create a new comment with "Hello world" as message
    And I see a comment with "Hello world" as message
    When I act as John
    And I enter in the folder named "Folder"
    Then I see that "Child folder" has unread comments
    And I open the unread comments for "Child folder"
    And I see that the details view is open
    And I see a comment with "Hello world" as message



  Scenario: search a comment
    Given I am logged in
    And I open the details view for "welcome.txt"
    And I open the "Comments" tab in the details view
    And I create a new comment with "Hello world" as message
    And I see a comment with "Hello world" as message
    When I search for "hello"
    # Search results for comments also include the user that wrote the comment.
    Then I see that the search result 1 is "user0Hello world"
    And I see that the search result 1 was found in "welcome.txt"

  Scenario: search a comment in a child folder
    Given I am logged in
    And I create a new folder named "Folder"
    And I enter in the folder named "Folder"
    And I create a new folder named "Child folder"
    And I open the details view for "Child folder"
    And I open the "Comments" tab in the details view
    And I create a new comment with "Hello world" as message
    And I see a comment with "Hello world" as message
    # The Files app is open again to reload the file list
    And I open the Files app
    When I search for "hello"
    # Search results for comments also include the user that wrote the comment.
    Then I see that the search result 1 is "user0Hello world"
    And I see that the search result 1 was found in "Folder/Child folder"

  Scenario: search a comment by a another user
    Given I act as John
    And I am logged in as the admin
    And I act as Jane
    And I am logged in
    And I act as John
    And I rename "welcome.txt" to "shared.txt"
    And I share "shared.txt" with "user0"
    And I see that the file is shared with "user0"
    And I act as Jane
    # The Files app is open again to reload the file list
    And I open the Files app
    And I open the details view for "shared.txt"
    And I open the "Comments" tab in the details view
    And I create a new comment with "Hello world" as message
    And I see a comment with "Hello world" as message
    When I act as John
    And I search for "hello"
    # Search results for comments also include the user that wrote the comment.
    Then I see that the search result 1 is "user0Hello world"
    And I see that the search result 1 was found in "shared.txt"
