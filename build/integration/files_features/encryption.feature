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

	Scenario: copy a folder with per-user keys
		# Setup encryption with per-user keys
		Given using new dav path
		And invoking occ with "app:enable encryption"
		And the command was successful
		And invoking occ with "encryption:disable-master-key" with input "y"
		And the command was successful
		And invoking occ with "encryption:enable"
		And the command was successful
		And user "user1" exists
		And User "user1" created a folder "/source"
		And User "user1" created a folder "/source/sub"
		And User "user1" uploads file with content "BLABLABLA" to "/source/sub/encrypted.txt"
		# The target folders only exist on the storage, not yet in the cache, while the files inside are written
		When User "user1" copies file "/source" to "/copy"
		Then the HTTP status code should be "201"
		And As an "user1"
		And Downloading file "/copy/sub/encrypted.txt"
		And Downloaded content should be "BLABLABLA"
		# Restore the initial encryption state
		And invoking occ with "encryption:disable"
		And the command was successful
		And invoking occ with "encryption:enable-master-key" with input "y"
		And the command was successful

	Scenario: copy a folder into a shared folder with per-user keys
		# Setup encryption with per-user keys
		Given using new dav path
		And invoking occ with "app:enable encryption"
		And the command was successful
		And invoking occ with "encryption:disable-master-key" with input "y"
		And the command was successful
		And invoking occ with "encryption:enable"
		And the command was successful
		And user "user1" exists
		And user "user2" exists
		# Log in once so that the key pair of the share recipient exists
		And User "user2" uploads file with content "BLABLABLA" to "/init.txt"
		And User "user1" created a folder "/shared"
		And User "user1" created a folder "/source"
		And User "user1" uploads file with content "BLABLABLA" to "/source/encrypted.txt"
		And as "user1" creating a share with
			| path | /shared |
			| shareType | 0 |
			| shareWith | user2 |
			| permissions | 31 |
		And the HTTP status code should be "200"
		# The share key of the recipient has to be created from the closest known parent
		When User "user1" copies file "/source" to "/shared/copy"
		Then the HTTP status code should be "201"
		And As an "user2"
		And Downloading file "/shared/copy/encrypted.txt"
		And Downloaded content should be "BLABLABLA"
		# Restore the initial encryption state
		And invoking occ with "encryption:disable"
		And the command was successful
		And invoking occ with "encryption:enable-master-key" with input "y"
		And the command was successful
