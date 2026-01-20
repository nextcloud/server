# SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
Feature: sharees_user

	Background:
		Given using api version "1"

	Scenario: Search for userid returns exact user
		Given user "test" with displayname "Test" exists
		And user "user1" exists
		And As an "user1"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned are
			| Test | 0 | test | test |
		And "users" sharees returned is empty

	Scenario: Search for userid returns exact user without sharee enumeration
		Given user "test" with displayname "Test" exists
		And user "user1" exists
		And As an "user1"
		And parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned are
			| Test | 0 | test | test |
		And "users" sharees returned is empty

	Scenario: Search for userid without shared group returns nothing with sharing in group only
		Given user "test" with displayname "Test" exists
		And group "test-group" exists
		And user "test" belongs to group "test-group"
		And user "user1" exists
		And As an "user1"
		And parameter "shareapi_only_share_with_group_members" of app "core" is set to "yes"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned is empty
		And "users" sharees returned is empty

	Scenario: Search for userid without shared group returns nothing with sharing in group only and without sharee enumeration
		Given user "test" with displayname "Test" exists
		And group "test-group" exists
		And user "test" belongs to group "test-group"
		And user "user1" exists
		And As an "user1"
		And parameter "shareapi_only_share_with_group_members" of app "core" is set to "yes"
		And parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned is empty
		And "users" sharees returned is empty

	Scenario: Search for userid with shared group returns exact user with sharing in group only
		Given user "test" with displayname "Test" exists
		And group "test-group" exists
		And user "test" belongs to group "test-group"
		And user "user1" exists
		And user "user1" belongs to group "test-group"
		And As an "user1"
		And parameter "shareapi_only_share_with_group_members" of app "core" is set to "yes"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned are
			| Test | 0 | test | test |
		And "users" sharees returned is empty

	Scenario: Search for userid with shared group returns exact user with sharing in group only and without sharee enumeration
		Given user "test" with displayname "Test" exists
		And group "test-group" exists
		And user "test" belongs to group "test-group"
		And user "user1" exists
		And user "user1" belongs to group "test-group"
		And As an "user1"
		And parameter "shareapi_only_share_with_group_members" of app "core" is set to "yes"
		And parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned are
			| Test | 0 | test | test |
		And "users" sharees returned is empty

	Scenario: Search for part of userid returns wide user
		Given user "test1" with displayname "Test One" exists
		And user "user1" exists
		And As an "user1"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned is empty
		And "users" sharees returned are
			| Test One | 0 | test1 | test1 |

	Scenario: Search for part of userid returns nothing without sharee enumeration
		Given user "test1" with displayname "Test One" exists
		And user "user1" exists
		And As an "user1"
		And parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned is empty
		And "users" sharees returned is empty

	Scenario: Search for part of userid returns wide users
		Given user "test1" with displayname "Test One" exists
		And user "test2" with displayname "Test Two" exists
		And user "user1" exists
		And As an "user1"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned is empty
		And "users" sharees returned are
			| Test One | 0 | test1 | test1 |
			| Test Two | 0 | test2 | test2 |

	Scenario: Search for part of userid returns nothing without sharee enumeration
		Given user "test1" with displayname "Test One" exists
		And user "test2" with displayname "Test Two" exists
		And user "user1" exists
		And As an "user1"
		And parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned is empty
		And "users" sharees returned is empty

	Scenario: Search for part of displayname returns exact user and wide users
		Given user "test0" with displayname "Test" exists
		And user "test1" with displayname "Test One" exists
		And user "test2" with displayname "Test Two" exists
		And user "user1" exists
		And As an "user1"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned are
			| Test | 0 | test0 | test0 |
		And "users" sharees returned are
			| Test One | 0 | test1 | test1 |
			| Test Two | 0 | test2 | test2 |

	Scenario: Search for part of displayname returns exact user without sharee enumeration
		Given user "test0" with displayname "Test" exists
		And user "test1" with displayname "Test One" exists
		And user "test2" with displayname "Test Two" exists
		And user "user1" exists
		And As an "user1"
		And parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned are
			| Test | 0 | test0 | test0 |
		And "users" sharees returned is empty

	Scenario: Search for part of userid with shared group returns wide user with sharing in group only
		Given user "test1" with displayname "Test One" exists
		And group "abc" exists
		And user "test1" belongs to group "abc"
		And group "xyz" exists
		And user "user1" exists
		And user "user1" belongs to group "abc"
		And user "user1" belongs to group "xyz"
		And As an "user1"
		And parameter "shareapi_only_share_with_group_members" of app "core" is set to "yes"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned is empty
		And "users" sharees returned are
			| Test One | 0 | test1 | test1 |

	Scenario: Search for part of userid with shared group returns nothing with sharing in group only and without sharee enumeration
		Given user "test1" with displayname "Test One" exists
		And group "abc" exists
		And user "test1" belongs to group "abc"
		And group "xyz" exists
		And user "user1" exists
		And user "user1" belongs to group "abc"
		And user "user1" belongs to group "xyz"
		And As an "user1"
		And parameter "shareapi_only_share_with_group_members" of app "core" is set to "yes"
		And parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned is empty
		And "users" sharees returned is empty

	Scenario: Search for part of userid with shared groups returns wide users with sharing in group only
		Given user "test1" with displayname "Test One" exists
		And user "test2" with displayname "Test Two" exists
		And group "abc" exists
		And user "test1" belongs to group "abc"
		And user "test2" belongs to group "abc"
		And group "xyz" exists
		And user "test1" belongs to group "xyz"
		And user "test2" belongs to group "xyz"
		And user "user1" exists
		And user "user1" belongs to group "abc"
		And user "user1" belongs to group "xyz"
		And As an "user1"
		And parameter "shareapi_only_share_with_group_members" of app "core" is set to "yes"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned is empty
		And "users" sharees returned are
			| Test One | 0 | test1 | test1 |
			| Test Two | 0 | test2 | test2 |

	Scenario: Search for part of userid with shared groups returns nothing with sharing in group only and without sharee enumeration
		Given user "test1" with displayname "Test One" exists
		And user "test2" with displayname "Test Two" exists
		And group "abc" exists
		And user "test1" belongs to group "abc"
		And user "test2" belongs to group "abc"
		And group "xyz" exists
		And user "test1" belongs to group "xyz"
		And user "test2" belongs to group "xyz"
		And user "user1" exists
		And user "user1" belongs to group "abc"
		And user "user1" belongs to group "xyz"
		And As an "user1"
		And parameter "shareapi_only_share_with_group_members" of app "core" is set to "yes"
		And parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned is empty
		And "users" sharees returned is empty

	Scenario: Search for part of userid with shared groups returns exact user and wide user with sharing in group only
		Given user "test" with displayname "Test One" exists
		And user "test2" with displayname "Test Two" exists
		And group "abc" exists
		And user "test" belongs to group "abc"
		And group "xyz" exists
		And user "test2" belongs to group "xyz"
		And user "user1" exists
		And user "user1" belongs to group "abc"
		And user "user1" belongs to group "xyz"
		And As an "user1"
		And parameter "shareapi_only_share_with_group_members" of app "core" is set to "yes"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned are
			| Test One | 0 | test | test |
		And "users" sharees returned are
			| Test Two | 0 | test2 | test2 |

	Scenario: Search for part of userid with shared groups returns exact user with sharing in group only and without sharee enumeration
		Given user "test" with displayname "Test One" exists
		And user "test2" with displayname "Test Two" exists
		And group "abc" exists
		And user "test" belongs to group "abc"
		And group "xyz" exists
		And user "test2" belongs to group "xyz"
		And user "user1" exists
		And user "user1" belongs to group "abc"
		And user "user1" belongs to group "xyz"
		And As an "user1"
		And parameter "shareapi_only_share_with_group_members" of app "core" is set to "yes"
		And parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned are
			| Test One | 0 | test | test |
		And "users" sharees returned is empty

	Scenario: Search for part of userid with shared group returns wide user with sharee enumeration limited to group
		Given user "test" with displayname "foo" exists
		And user "test1" exists
		And user "test2" exists
		And group "groupA" exists
		And group "groupB" exists
		And user "test" belongs to group "groupA"
		And user "test1" belongs to group "groupA"
		And user "test2" belongs to group "groupB"
		And As an "test"
		And parameter "shareapi_restrict_user_enumeration_to_group" of app "core" is set to "yes"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned is empty
		And "users" sharees returned are
			| test1 | 0 | test1 | test1 |

	Scenario: Search for exact userid with shared group returns nothing without sharee enumeration and without full match userid enumeration
		Given user "test" with displayname "foo" exists
		And user "test1" with displayname "Test One" exists
		And user "test2" with displayname "Test Two" exists
		And group "groupA" exists
		And user "test" belongs to group "groupA"
		And user "test1" belongs to group "groupA"
		And user "test2" belongs to group "groupA"
		And As an "test"
		And parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
		And parameter "shareapi_restrict_user_enumeration_full_match_userid" of app "core" is set to "no"
		When getting sharees for
			| search   | test1 |
			| itemType | file  |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned is empty
		And "users" sharees returned is empty

	Scenario: Search for displayname returns exact user without sharee enumeration and without full match userid enumeration
		Given user "test" with displayname "foo" exists
		And user "test1" with displayname "Test One" exists
		And user "test2" with displayname "Test Two" exists
		And group "groupA" exists
		And user "test" belongs to group "groupA"
		And user "test1" belongs to group "groupA"
		And user "test2" belongs to group "groupA"
		And As an "test"
		And parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
		And parameter "shareapi_restrict_user_enumeration_full_match_user_id" of app "core" is set to "no"
		When getting sharees for
			| search   | Test One |
			| itemType | file     |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned are
			| Test One | 0 | test1 | test1 |
		And "users" sharees returned is empty

	Scenario: Search for part of displayname returns exact user without sharee enumeration and with ignoring full match of second displayname
		Given user "test" with displayname "foo" exists
		And user "test1" with displayname "Test One (Second displayname for user 1)" exists
		And user "test2" with displayname "Test Two (Second displayname for user 2)" exists
		And group "groupA" exists
		And user "test" belongs to group "groupA"
		And user "test1" belongs to group "groupA"
		And user "test2" belongs to group "groupA"
		And As an "test"
		And parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
		And parameter "shareapi_restrict_user_enumeration_full_match_ignore_second_dn" of app "core" is set to "yes"
		When getting sharees for
			| search   | Test One |
			| itemType | file     |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned are
			| Test One (Second displayname for user 1) | 0 | test1 | test1 |
		And "users" sharees returned is empty

	Scenario: Search for exact userid with shared group returns exact user with sharee enumeration limited to group
		Given user "test" with displayname "foo" exists
		And user "test1" exists
		And user "test2" exists
		And group "groupA" exists
		And group "groupB" exists
		And user "test" belongs to group "groupA"
		And user "test1" belongs to group "groupA"
		And user "test2" belongs to group "groupB"
		And As an "test"
		And parameter "shareapi_restrict_user_enumeration_to_group" of app "core" is set to "yes"
		When getting sharees for
			| search   | test1 |
			| itemType | file  |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned are
			| test1 | 0 | test1 | test1 |
		And "users" sharees returned is empty

	Scenario: Search for part of userid with shared group returns wide user with sharee enumeration limited to group
		Given user "test1" with displayname "Test One" exists
		And group "test-group" exists
		And user "test1" belongs to group "test-group"
		And user "user1" exists
		And user "user1" belongs to group "test-group"
		And As an "user1"
		And parameter "shareapi_restrict_user_enumeration_to_group" of app "core" is set to "yes"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned is empty
		And "users" sharees returned are
			| Test One | 0 | test1 | test1 |

	Scenario: Search for part of userid without shared group returns nothing with sharee enumeration limited to group
		Given user "test1" with displayname "Test One" exists
		And user "user1" exists
		And As an "user1"
		And parameter "shareapi_restrict_user_enumeration_to_group" of app "core" is set to "yes"
		When getting sharees for
			| search   | test |
			| itemType | file |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned is empty
		And "users" sharees returned is empty

	Scenario: Search for exact userid without shared group returns exact user with sharee enumeration limited to group
		Given user "test1" with displayname "Test One" exists
		And user "user1" exists
		And As an "user1"
		And parameter "shareapi_restrict_user_enumeration_to_group" of app "core" is set to "yes"
		When getting sharees for
			| search   | test1 |
			| itemType | file  |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned are
			| Test One | 0 | test1 | test1 |
		And "users" sharees returned is empty

	Scenario: Search for exact email without shared group returns exact user with sharee enumeration limited to group
		Given user "test1" with displayname "Test One" exists
		And As an "admin"
		And sending "PUT" to "/cloud/users/test1" with
			| key   | email            |
			| value | test@example.com |
		And user "user1" exists
		And As an "user1"
		And parameter "shareapi_restrict_user_enumeration_to_group" of app "core" is set to "yes"
		When getting sharees for
			| search   | test@example.com |
			| itemType | file             |
		Then the OCS status code should be "100"
		And the HTTP status code should be "200"
		And "exact users" sharees returned are
			| Test One | 0 | test1 | test@example.com |
		And "users" sharees returned is empty
