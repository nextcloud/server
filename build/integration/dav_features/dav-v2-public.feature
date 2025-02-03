# SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
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

	Scenario: Downloading a file from public share with Ajax header
		Given using new dav path
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And As an "user1"
		And user "user1" created a folder "/testshare"
		When User "user1" uploads file "data/green-square-256.png" to "/testshare/image.png"
		And as "user1" creating a share with
		  | path | testshare |
		  | shareType | 3 |
		  | permissions | 1 |
		And As an "user0"
		Given using new public dav path
		When Downloading public file "/image.png"
		Then the downloaded file has the content of "/testshare/image.png" from "user1" data

	# Test that downloading files work to ensure e.g. the viewer works or files can be downloaded
	Scenario: Downloading a file from public share without Ajax header and disabled s2s share
		Given using new dav path
		And As an "admin"
		And user "user0" exists
		And user "user1" exists
		And As an "user1"
		And user "user1" created a folder "/testshare"
		When User "user1" uploads file "data/green-square-256.png" to "/testshare/image.png"
		And as "user1" creating a share with
		  | path | testshare |
		  | shareType | 3 |
		  | permissions | 1 |
		And As an "user0"
		Given parameter "outgoing_server2server_share_enabled" of app "files_sharing" is set to "no"
		Given using new public dav path
		When Downloading public file "/image.png" without ajax header
		Then the downloaded file has the content of "/testshare/image.png" from "user1" data
