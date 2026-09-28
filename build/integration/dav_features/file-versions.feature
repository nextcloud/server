# SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

Feature: file-versions
	Background:
		Given using new dav path

  # Regression test for file version downloads returning "404 File not found: versions in 'root'".
  # The versions/trash bin collections are attached to the DAV root lazily during beforeMethod,
  # so an early GET handler that resolved the request path too eagerly aborted the request.
  # This exercises the full plugin stack: a real previous version must be downloadable via DAV.
	Scenario: Download a previous version of a file via the versions DAV endpoint
		Given user "admin" uploads file with content "first version" and mtime "1111111111" to "/versioned.txt"
		And user "admin" uploads file with content "second version" and mtime "2222222222" to "/versioned.txt"
		When user "admin" downloads version "1111111111" of file "/versioned.txt"
		Then the HTTP status code should be "200"
		And Downloaded content should be "first version"
