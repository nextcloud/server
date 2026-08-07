<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\ResourceBooking;

use OCA\DAV\CalDAV\ResourceBooking\RoomPrincipalBackend;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;

#[Group('DB')]
class RoomPrincipalBackendTest extends AbstractPrincipalBackendTestCase {
	protected function setUp(): void {
		parent::setUp();

		$this->principalBackend = new RoomPrincipalBackend(Server::get(IDBConnection::class),
			$this->userSession, $this->groupManager, $this->logger, $this->proxyMapper);

		$this->mainDbTable = 'calendar_rooms';
		$this->metadataDbTable = 'calendar_rooms_md';
		$this->foreignKey = 'room_id';

		$this->principalPrefix = 'principals/calendar-rooms';
		$this->expectedCUType = 'ROOM';

		$this->createTestDatasetInDb();
	}
}
