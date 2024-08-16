<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\ResourceBooking;

use OCA\DAV\CalDAV\Proxy\ProxyMapper;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Class RoomPrincipalBackend
 *
 * @package OCA\DAV\CalDAV\ResourceBooking
 */
class RoomPrincipalBackend extends AbstractPrincipalBackend {

	/**
	 * RoomPrincipalBackend constructor.
	 */
	public function __construct(IDBConnection $dbConnection,
		IUserSession $userSession,
		IGroupManager $groupManager,
		LoggerInterface $logger,
		ProxyMapper $proxyMapper) {
		parent::__construct($dbConnection, $userSession, $groupManager, $logger,
			$proxyMapper, 'principals/calendar-rooms', 'room', 'ROOM');
	}
}
