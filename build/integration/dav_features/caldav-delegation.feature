# SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
Feature: calendar delegation
  Calendar delegation grants another user/principal control of a calendar account,
  including all calendars the delegator can access.

  Scenario: admin grants user0 read access to her calendar account
    Given user "admin" exists
    And user "user0" exists
    When "admin" updates property "{DAV:}group-member-set" to href "/remote.php/dav/principals/users/user0" of principal "users/admin/calendar-proxy-read" on the endpoint "/remote.php/dav/principals/"
    Then The CalDAV response should be multi status
    And The CalDAV response should contain an href "/remote.php/dav/principals/users/admin/calendar-proxy-read"
    And The CalDAV response should contain a property "{DAV:}group-member-set"

  Scenario: admin grants write access to her calendar account
    Given user "admin" exists
    And user "user0" exists
    When "admin" updates property "{DAV:}group-member-set" to href "/remote.php/dav/principals/users/user0" of principal "users/admin/calendar-proxy-write" on the endpoint "/remote.php/dav/principals/"
    Then The CalDAV response should be multi status
    And The CalDAV response should contain an href "/remote.php/dav/principals/users/admin/calendar-proxy-write"
    And The CalDAV response should contain a property "{DAV:}group-member-set"