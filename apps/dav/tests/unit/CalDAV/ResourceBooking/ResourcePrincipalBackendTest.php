<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\ResourceBooking;

use OCA\DAV\CalDAV\ResourceBooking\ResourcePrincipalBackend;

class ResourcePrincipalBackendTest extends AbstractPrincipalBackendTest {
	protected function setUp(): void {
		parent::setUp();

		$this->principalBackend = new ResourcePrincipalBackend(self::$realDatabase,
			$this->userSession, $this->groupManager, $this->logger, $this->proxyMapper);

		$this->mainDbTable = 'calendar_resources';
		$this->metadataDbTable = 'calendar_resources_md';
		$this->foreignKey = 'resource_id';

		$this->principalPrefix = 'principals/calendar-resources';
		$this->expectedCUType = 'RESOURCE';

		$this->createTestDatasetInDb();
	}
}
