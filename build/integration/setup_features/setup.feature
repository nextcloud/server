# SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
Feature: setup

	Scenario: setup page is shown properly
		When requesting "/index.php" with "GET"
		Then the HTTP status code should be "200"
