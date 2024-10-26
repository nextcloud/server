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
 * Class ResourcePrincipalBackend
 *
 * @package OCA\DAV\CalDAV\ResourceBooking
 */
class ResourcePrincipalBackend extends AbstractPrincipalBackend {

	/**
	 * ResourcePrincipalBackend constructor.
	 */
	public function __construct(IDBConnection $dbConnection,
		IUserSession $userSession,
		IGroupManager $groupManager,
		LoggerInterface $logger,
		ProxyMapper $proxyMapper) {
		parent::__construct($dbConnection, $userSession, $groupManager, $logger,
			$proxyMapper, 'principals/calendar-resources', 'resource', 'RESOURCE');
	}
}
