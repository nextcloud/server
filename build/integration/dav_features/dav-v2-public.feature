# SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
Feature: dav-v2-public
	Background:
		Given using api version "1"

	Scenario: See note to recipient in public shares
		Given using new dav path
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And As an "user1"
		And user "user1" created a folder "/testshare"
		And as "user1" creating a share with
		  | path | testshare |
		  | shareType | 3 |
		  | permissions | 1 |
		  | note | Hello |
		And As an "user0"
		Given using new public dav path
		When Requesting share note on dav endpoint
		Then the single response should contain a property "{http://nextcloud.org/ns}note" with value "Hello"
