# SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

Feature: Windows compatible filenames
	Background:
		Given using api version "1"
		And using new dav path
		And As an "admin"

	Scenario: prevent upload files with invalid name
		Given As an "admin"
		And user "user0" exists
		And invoking occ with "files:windows-compatible-filenames --enable"
		Given User "user0" created a folder "/com1"
		Then as "user0" the file "/com1" does not exist

	Scenario: renaming a folder with invalid name
		Given As an "admin"
		When invoking occ with "files:windows-compatible-filenames --disable"
		And user "user0" exists
		Given User "user0" created a folder "/aux"
		When invoking occ with "files:windows-compatible-filenames --enable"
		And invoking occ with "files:sanitize-filenames user0"
		Then as "user0" the file "/aux" does not exist
		And as "user0" the file "/aux (renamed)" exists

	Scenario: renaming a file with invalid base name
		Given As an "admin"
		When invoking occ with "files:windows-compatible-filenames --disable"
		And user "user0" exists
		When User "user0" uploads file with content "hello" to "/com0.txt"
		And invoking occ with "files:windows-compatible-filenames --enable"
		And invoking occ with "files:sanitize-filenames user0"
		Then as "user0" the file "/com0.txt" does not exist
		And as "user0" the file "/com0 (renamed).txt" exists

	Scenario: renaming a file with invalid extension
		Given As an "admin"
		When invoking occ with "files:windows-compatible-filenames --disable"
		And user "user0" exists
		When User "user0" uploads file with content "hello" to "/foo.txt."
		And as "user0" the file "/foo.txt." exists
		And invoking occ with "files:windows-compatible-filenames --enable"
		And invoking occ with "files:sanitize-filenames user0"
		Then as "user0" the file "/foo.txt." does not exist
		And as "user0" the file "/foo.txt" exists

	Scenario: renaming a file with invalid character
		Given As an "admin"
		When invoking occ with "files:windows-compatible-filenames --disable"
		And user "user0" exists
		When User "user0" uploads file with content "hello" to "/2*2=4.txt"
		And as "user0" the file "/2*2=4.txt" exists
		And invoking occ with "files:windows-compatible-filenames --enable"
		And invoking occ with "files:sanitize-filenames user0"
		Then as "user0" the file "/2*2=4.txt" does not exist
		And as "user0" the file "/2_2=4.txt" exists

	Scenario: renaming a file with invalid character and replacement setup
		Given As an "admin"
		When invoking occ with "files:windows-compatible-filenames --disable"
		And user "user0" exists
		When User "user0" uploads file with content "hello" to "/2*3=6.txt"
		And as "user0" the file "/2*3=6.txt" exists
		And invoking occ with "files:windows-compatible-filenames --enable"
		And invoking occ with "files:sanitize-filenames --char-replacement + user0"
		Then as "user0" the file "/2*3=6.txt" does not exist
		And as "user0" the file "/2+3=6.txt" exists
