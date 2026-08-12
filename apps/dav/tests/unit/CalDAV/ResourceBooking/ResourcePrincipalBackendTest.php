<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\ResourceBooking;

use OCA\DAV\CalDAV\ResourceBooking\ResourcePrincipalBackend;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;

#[Group('DB')]
class ResourcePrincipalBackendTest extends AbstractPrincipalBackendTestCase {
	protected function setUp(): void {
		parent::setUp();

		$this->principalBackend = new ResourcePrincipalBackend(Server::get(IDBConnection::class),
			$this->userSession, $this->groupManager, $this->logger, $this->proxyMapper);

		$this->mainDbTable = 'calendar_resources';
		$this->metadataDbTable = 'calendar_resources_md';
		$this->foreignKey = 'resource_id';

		$this->principalPrefix = 'principals/calendar-resources';
		$this->expectedCUType = 'RESOURCE';

		$this->createTestDatasetInDb();
	}
}
