<?php

declare(strict_types=1);

/*
 * @copyright 2024 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use OCA\DAV\DAV\Sharing\SharingMapper;
use OCP\IDBConnection;
use OCP\Server;
use Test\TestCase;

/**
 * @group DB
 */
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
