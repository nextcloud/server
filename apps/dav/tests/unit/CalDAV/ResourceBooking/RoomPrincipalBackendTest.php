<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\ResourceBooking;

use OCA\DAV\CalDAV\ResourceBooking\RoomPrincipalBackend;

class RoomPrincipalBackendTest extends AbstractPrincipalBackendTest {
	protected function setUp(): void {
		parent::setUp();

		$this->principalBackend = new RoomPrincipalBackend(self::$realDatabase,
			$this->userSession, $this->groupManager, $this->logger, $this->proxyMapper);

		$this->mainDbTable = 'calendar_rooms';
		$this->metadataDbTable = 'calendar_rooms_md';
		$this->foreignKey = 'room_id';

		$this->principalPrefix = 'principals/calendar-rooms';
		$this->expectedCUType = 'ROOM';

		$this->createTestDatasetInDb();
	}
}
