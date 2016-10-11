Feature: caldav
  Scenario: Accessing a not existing calendar of another user
    Given user "user0" exists
    When "admin" requests calendar "user0/MyCalendar"
    Then The CalDAV HTTP status code should be "404"
    And The exception is "Sabre\DAV\Exception\NotFound"
    And The error message is "Node with name 'MyCalendar' could not be found"

  # Blocked by https://github.com/php/php-src/pull/1417
  #Scenario: Accessing a not shared calendar of another user
  #  Given user "user0" exists
  #  Given "admin" creates a calendar named "MyCalendar"
  #  Given The CalDAV HTTP status code should be "201"
  #  When "user0" requests calendar "admin/MyCalendar"
  #  Then The CalDAV HTTP status code should be "404"
  #  And The exception is "Sabre\DAV\Exception\NotFound"
  #  And The error message is "Node with name 'MyCalendar' could not be found"

  Scenario: Accessing a not existing calendar of myself
    Given user "user0" exists
    When "user0" requests calendar "admin/MyCalendar"
    Then The CalDAV HTTP status code should be "404"
    And The exception is "Sabre\DAV\Exception\NotFound"
    And The error message is "Node with name 'MyCalendar' could not be found"

  # Blocked by https://github.com/php/php-src/pull/1417
  #Scenario: Creating a new calendar
  #  When "admin" creates a calendar named "MyCalendar"
  #  Then The CalDAV HTTP status code should be "201"
  #  And "admin" requests calendar "admin/MyCalendar"
  #  Then The CalDAV HTTP status code should be "200"
