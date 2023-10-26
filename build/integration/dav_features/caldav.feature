Feature: caldav
  Scenario: Accessing a not existing calendar of another user
    Given user "user0" exists
    When "admin" requests calendar "user0/MyCalendar" on the endpoint "/remote.php/dav/calendars/"
    Then The CalDAV HTTP status code should be "404"
    And The exception is "Sabre\DAV\Exception\NotFound"
    And The error message is "Node with name 'MyCalendar' could not be found"

  Scenario: Accessing a not shared calendar of another user
    Given user "user0" exists
    Given "admin" creates a calendar named "MyCalendar"
    Given The CalDAV HTTP status code should be "201"
    When "user0" requests calendar "admin/MyCalendar" on the endpoint "/remote.php/dav/calendars/"
    Then The CalDAV HTTP status code should be "404"
    And The exception is "Sabre\DAV\Exception\NotFound"
    And The error message is "Node with name 'MyCalendar' could not be found"

  Scenario: Accessing a not shared calendar of another user via the legacy endpoint
    Given user "user0" exists
    Given "admin" creates a calendar named "MyCalendar"
    Given The CalDAV HTTP status code should be "201"
    When "user0" requests calendar "admin/MyCalendar" on the endpoint "/remote.php/caldav/calendars/"
    Then The CalDAV HTTP status code should be "404"
    And The exception is "Sabre\DAV\Exception\NotFound"
    And The error message is "Node with name 'MyCalendar' could not be found"

  Scenario: Accessing a not existing calendar of another user
    Given user "user0" exists
    When "user0" requests calendar "admin/MyCalendar" on the endpoint "/remote.php/dav/calendars/"
    Then The CalDAV HTTP status code should be "404"
    And The exception is "Sabre\DAV\Exception\NotFound"
    And The error message is "Node with name 'MyCalendar' could not be found"

  Scenario: Accessing a not existing calendar of another user via the legacy endpoint
    Given user "user0" exists
    When "user0" requests calendar "admin/MyCalendar" on the endpoint "/remote.php/caldav/calendars/"
    Then The CalDAV HTTP status code should be "404"
    And The exception is "Sabre\DAV\Exception\NotFound"
    And The error message is "Node with name 'MyCalendar' could not be found"

  Scenario: Accessing a not existing calendar of myself
    Given user "user0" exists
    When "user0" requests calendar "admin/MyCalendar" on the endpoint "/remote.php/dav/calendars/"
    Then The CalDAV HTTP status code should be "404"
    And The exception is "Sabre\DAV\Exception\NotFound"
    And The error message is "Node with name 'MyCalendar' could not be found"

  Scenario: Creating a new calendar
    When "admin" creates a calendar named "MyCalendar"
    Then The CalDAV HTTP status code should be "201"
    And "admin" requests calendar "admin/MyCalendar" on the endpoint "/remote.php/dav/calendars/"
    Then The CalDAV HTTP status code should be "207"

  Scenario: Propfind on public calendar endpoint without calendars
    When "admin" creates a calendar named "MyCalendar"
    Then The CalDAV HTTP status code should be "201"
    And "admin" publicly shares the calendar named "MyCalendar"
    Then The CalDAV HTTP status code should be "202"
    When "admin" requests calendar "/" on the endpoint "/remote.php/dav/public-calendars"
    Then The CalDAV HTTP status code should be "207"
    Then There should be "0" calendars in the response body

  Scenario: Create calendar request for non-existing calendar of another user
    Given user "user0" exists
    When "user0" sends a create calendar request to "admin/MyCalendar2" on the endpoint "/remote.php/dav/calendars/"
    Then The CalDAV HTTP status code should be "404"
    And The exception is "Sabre\DAV\Exception\NotFound"
    And The error message is "Node with name 'admin' could not be found"

  Scenario: Create calendar request for existing calendar of another user
    Given user "user0" exists
    When "admin" creates a calendar named "MyCalendar2"
    Then The CalDAV HTTP status code should be "201"
    When "user0" sends a create calendar request to "admin/MyCalendar2" on the endpoint "/remote.php/dav/calendars/"
    Then The CalDAV HTTP status code should be "404"
    And The exception is "Sabre\DAV\Exception\NotFound"
    And The error message is "Node with name 'admin' could not be found"
