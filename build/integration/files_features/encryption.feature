# SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
Feature: encryption

	Scenario: encryption tests
		# Setup encryption
		Given using new dav path
		And user "user0" exists
		And User "user0" uploads file with content "BLABLABLA" to "/non-encrypted.txt"
		And invoking occ with "app:enable encryption"
		And the command was successful
		And invoking occ with "encryption:enable"
		And the command was successful
		And As an "user0"
		And User "user0" uploads file with content "BLABLABLA" to "/encrypted.txt"
		# Check both encrypted and non-encrypted files can be read
		When Downloading file "/encrypted.txt" with range "bytes=0-8"
		Then Downloaded content should be "BLABLABLA"
		When Downloading file "/non-encrypted.txt" with range "bytes=0-8"
		Then Downloaded content should be "BLABLABLA"
		When invoking occ with "info:file user0/files/encrypted.txt"
		And the command was successful
		Then the command output contains the text "server-side encrypted: yes"
		When invoking occ with "info:file user0/files/non-encrypted.txt"
		And the command was successful
		Then the command output does not contain the text "server-side encrypted: yes"
		# Run encryption:encrypt-all and checks that non-encrypted file gets encrypted
		When invoking occ with "encryption:encrypt-all" with input "y"
		And the command was successful
		And invoking occ with "info:file user0/files/non-encrypted.txt"
		And the command was successful
		Then the command output contains the text "server-side encrypted: yes"
		And Downloading file "/non-encrypted.txt" with range "bytes=0-8"
		And Downloaded content should be "BLABLABLA"
		# Run encryption:decrypt-all and checks that files gets decrypted
		When invoking occ with "encryption:decrypt-all" with input "y"
		And the command was successful
		And invoking occ with "info:file user0/files/non-encrypted.txt"
		And the command was successful
		Then the command output does not contain the text "server-side encrypted: yes"
		And Downloading file "/non-encrypted.txt" with range "bytes=0-8"
		And Downloaded content should be "BLABLABLA"
