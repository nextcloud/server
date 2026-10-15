<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\integration\DAV\Sharing;

use OCA\DAV\DAV\Sharing\SharingMapper;
use OCP\IDBConnection;
use OCP\Server;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class SharingMapperTest extends TestCase {

	private SharingMapper $mapper;
	private IDBConnection $db;

	protected function setUp(): void {
		parent::setUp();

		$this->db = Server::get(IDBConnection::class);
		$this->mapper = new SharingMapper($this->db);
		$qb = $this->db->getQueryBuilder();
		$qb->delete('dav_shares')->executeStatement();
	}

	public function testShareAndGet(): void {
		$resourceId = 42;
		$resourceType = 'calendar';
		$access = 3;
		$principal = 'principals/users/bob';
		$this->mapper->share($resourceId, $resourceType, $access, $principal);
		$shares = $this->mapper->getSharesForId($resourceId, $resourceType);
		$this->assertCount(1, $shares);
	}

	public function testShareDelete(): void {
		$resourceId = 42;
		$resourceType = 'calendar';
		$access = 3;
		$principal = 'principals/users/bob';
		$this->mapper->share($resourceId, $resourceType, $access, $principal);
		$this->mapper->deleteShare($resourceId, $resourceType, $principal);
		$shares = $this->mapper->getSharesForId($resourceId, $resourceType);
		$this->assertEmpty($shares);
	}

	public function testShareUnshare(): void {
		$resourceId = 42;
		$resourceType = 'calendar';
		$access = 3;
		$principal = 'principals/groups/alicegroup';
		$userPrincipal = 'principals/users/alice';
		$this->mapper->share($resourceId, $resourceType, $access, $principal);
		$this->mapper->unshare($resourceId, $resourceType, $userPrincipal);
		$shares = $this->mapper->getSharesForId($resourceId, $resourceType);
		$this->assertCount(1, $shares);
	}

	public function testShareDeleteAll(): void {
		$resourceId = 42;
		$resourceType = 'calendar';
		$access = 3;
		$principal = 'principals/groups/alicegroup';
		$userPrincipal = 'principals/users/alice';
		$this->mapper->share($resourceId, $resourceType, $access, $principal);
		$this->mapper->unshare($resourceId, $resourceType, $userPrincipal);
		$shares = $this->mapper->getSharesForId($resourceId, $resourceType);
		$this->assertCount(1, $shares);
		$this->mapper->deleteAllShares($resourceId, $resourceType);
		$shares = $this->mapper->getSharesForId($resourceId, $resourceType);
		$this->assertEmpty($shares);
	}

	public function testShareDeleteAllForUser(): void {
		$resourceId = 42;
		$resourceType = 'calendar';
		$access = 3;
		$principal = 'principals/groups/alicegroup';
		$this->mapper->share($resourceId, $resourceType, $access, $principal);
		$shares = $this->mapper->getSharesForId($resourceId, $resourceType);
		$this->assertCount(1, $shares);
		$this->mapper->deleteAllSharesByUser($principal, $resourceType);
		$shares = $this->mapper->getSharesForId($resourceId, $resourceType);
		$this->assertEmpty($shares);
	}

}
