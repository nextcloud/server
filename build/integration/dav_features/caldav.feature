# SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
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
    And The error message is "Calendar with name 'MyCalendar' could not be found"

  Scenario: Accessing a not shared calendar of another user via the legacy endpoint
    Given user "user0" exists
    Given "admin" creates a calendar named "MyCalendar"
    Given The CalDAV HTTP status code should be "201"
    When "user0" requests calendar "admin/MyCalendar" on the endpoint "/remote.php/caldav/calendars/"
    Then The CalDAV HTTP status code should be "404"
    And The exception is "Sabre\DAV\Exception\NotFound"
    And The error message is "Calendar with name 'MyCalendar' could not be found"

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

  Scenario: Update a principal's schedule-default-calendar-URL
    Given user "user0" exists
    And "user0" creates a calendar named "MyCalendar2"
    When "user0" updates property "{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL" to href "/remote.php/dav/calendars/user0/MyCalendar2/" of principal "users/user0" on the endpoint "/remote.php/dav/principals/"
    Then The CalDAV response should be multi status
    And The CalDAV response should contain a property "{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL"
    When "user0" requests principal "users/user0" on the endpoint "/remote.php/dav/principals/"
    Then The CalDAV response should be multi status
    And The CalDAV response should contain a property "{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL" with a href value "/remote.php/dav/calendars/user0/MyCalendar2/"

  Scenario: Should create default calendar on first login
    Given user "first-login" exists
    When "first-login" requests calendar "first-login/personal" on the endpoint "/remote.php/dav/calendars/"
    Then The CalDAV HTTP status code should be "207"

  Scenario: Publishing a calendar is forbidden when link sharing is disabled
    Given parameter "shareapi_allow_links" of app "core" is set to "no"
    When "admin" creates a calendar named "MyCalendar"
    Then The CalDAV HTTP status code should be "201"
    When "admin" requests the sharing modes of the calendar named "MyCalendar"
    Then The calendar should be shareable
    And The calendar should not be publishable
    When "admin" publicly shares the calendar named "MyCalendar"
    Then The CalDAV HTTP status code should be "403"
    When "admin" requests the sharing modes of the calendar named "MyCalendar"
    Then The calendar should not be published

  Scenario: Published calendars are blocked but can be unpublished when link sharing gets disabled
    Given "admin" creates a calendar named "MyCalendar"
    And "admin" publicly shares the calendar named "MyCalendar"
    And The CalDAV HTTP status code should be "202"
    When "admin" requests the public link of the calendar named "MyCalendar"
    Then The CalDAV HTTP status code should be "207"
    Given parameter "shareapi_allow_links" of app "core" is set to "no"
    When "admin" requests the public link of the calendar named "MyCalendar"
    Then The CalDAV HTTP status code should be "403"
    When "admin" unpublishes the calendar named "MyCalendar"
    Then The CalDAV HTTP status code should be "200"
    When "admin" requests the sharing modes of the calendar named "MyCalendar"
    Then The calendar should not be published

  Scenario: Users of a group excluded from link sharing cannot publish calendars
    Given user "user0" exists
    And group "group1" exists
    And user "user0" belongs to group "group1"
    And link sharing is disabled for group "group1"
    When "user0" creates a calendar named "MyCalendar"
    Then The CalDAV HTTP status code should be "201"
    When "user0" requests the sharing modes of the calendar named "MyCalendar"
    Then The calendar should be shareable
    And The calendar should not be publishable
    When "user0" publicly shares the calendar named "MyCalendar"
    Then The CalDAV HTTP status code should be "403"
    When "admin" creates a calendar named "MyCalendar"
    And "admin" requests the sharing modes of the calendar named "MyCalendar"
    Then The calendar should be publishable
    When "admin" publicly shares the calendar named "MyCalendar"
    Then The CalDAV HTTP status code should be "202"
