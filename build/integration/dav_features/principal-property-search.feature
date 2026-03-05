# SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

Feature: principal-property-search
	Background:
		Given user "user0" exists
		Given As an "admin"
		Given invoking occ with "app:enable --force testing"

	Scenario: Find a principal by a given displayname
		When searching for a principal matching "user0"
		Then The search HTTP status code should be "207"
		And The search response should contain "<d:href>/remote.php/dav/principals/users/user0/</d:href>"
