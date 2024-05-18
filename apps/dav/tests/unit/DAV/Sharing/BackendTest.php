<?php

declare(strict_types=1);
/*
 * @copyright 2024 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace OCA\DAV\Tests\unit\DAV\Sharing;

use OCA\DAV\CalDAV\Sharing\Backend as CalendarSharingBackend;
use OCA\DAV\CalDAV\Sharing\Service;
use OCA\DAV\CardDAV\Sharing\Backend as ContactsSharingBackend;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\Sharing\Backend;
use OCA\DAV\DAV\Sharing\IShareable;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class BackendTest extends TestCase {

	private IDBConnection|MockObject $db;
	private IUserManager|MockObject $userManager;
	private IGroupManager|MockObject $groupManager;
	private MockObject|Principal $principalBackend;
	private MockObject|ICache $shareCache;
	private LoggerInterface|MockObject $logger;
	private MockObject|ICacheFactory $cacheFactory;
	private Service|MockObject $calendarService;
	private CalendarSharingBackend $backend;

	protected function setUp(): void {
		parent::setUp();
		$this->db = $this->createMock(IDBConnection::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->principalBackend = $this->createMock(Principal::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->shareCache = $this->createMock(ICache::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->calendarService = $this->createMock(Service::class);
		$this->cacheFactory->expects(self::any())
			->method('createInMemory')
			->willReturn($this->shareCache);

		$this->backend = new CalendarSharingBackend(
			$this->userManager,
			$this->groupManager,
			$this->principalBackend,
			$this->cacheFactory,
			$this->calendarService,
			$this->logger,
		);
	}

	public function testUpdateShareCalendarBob(): void {
		$shareable = $this->createConfiguredMock(IShareable::class, [
			'getOwner' => 'principals/users/alice',
			'getResourceId' => 42,
		]);
		$add = [
			[
				'href' => 'principal:principals/users/bob',
				'readOnly' => true,
			]
		];
		$principal = 'principals/users/bob';

		$this->shareCache->expects(self::once())
			->method('clear');
		$this->principalBackend->expects(self::once())
			->method('findByUri')
			->willReturn($principal);
		$this->userManager->expects(self::once())
			->method('userExists')
			->willReturn(true);
		$this->groupManager->expects(self::never())
			->method('groupExists');
		$this->calendarService->expects(self::once())
			->method('shareWith')
			->with($shareable->getResourceId(), $principal, Backend::ACCESS_READ);

		$this->backend->updateShares($shareable, $add, []);
	}

	public function testUpdateShareCalendarGroup(): void {
		$shareable = $this->createConfiguredMock(IShareable::class, [
			'getOwner' => 'principals/users/alice',
			'getResourceId' => 42,
		]);
		$add = [
			[
				'href' => 'principal:principals/groups/bob',
				'readOnly' => true,
			]
		];
		$principal = 'principals/groups/bob';

		$this->shareCache->expects(self::once())
			->method('clear');
		$this->principalBackend->expects(self::once())
			->method('findByUri')
			->willReturn($principal);
		$this->userManager->expects(self::never())
			->method('userExists');
		$this->groupManager->expects(self::once())
			->method('groupExists')
			->willReturn(true);
		$this->calendarService->expects(self::once())
			->method('shareWith')
			->with($shareable->getResourceId(), $principal, Backend::ACCESS_READ);

		$this->backend->updateShares($shareable, $add, []);
	}

	public function testUpdateShareContactsBob(): void {
		$shareable = $this->createConfiguredMock(IShareable::class, [
			'getOwner' => 'principals/users/alice',
			'getResourceId' => 42,
		]);
		$add = [
			[
				'href' => 'principal:principals/users/bob',
				'readOnly' => true,
			]
		];
		$principal = 'principals/users/bob';

		$this->shareCache->expects(self::once())
			->method('clear');
		$this->principalBackend->expects(self::once())
			->method('findByUri')
			->willReturn($principal);
		$this->userManager->expects(self::once())
			->method('userExists')
			->willReturn(true);
		$this->groupManager->expects(self::never())
			->method('groupExists');
		$this->calendarService->expects(self::once())
			->method('shareWith')
			->with($shareable->getResourceId(), $principal, Backend::ACCESS_READ);

		$this->backend->updateShares($shareable, $add, []);
	}

	public function testUpdateShareContactsGroup(): void {
		$shareable = $this->createConfiguredMock(IShareable::class, [
			'getOwner' => 'principals/users/alice',
			'getResourceId' => 42,
		]);
		$add = [
			[
				'href' => 'principal:principals/groups/bob',
				'readOnly' => true,
			]
		];
		$principal = 'principals/groups/bob';

		$this->shareCache->expects(self::once())
			->method('clear');
		$this->principalBackend->expects(self::once())
			->method('findByUri')
			->willReturn($principal);
		$this->userManager->expects(self::never())
			->method('userExists');
		$this->groupManager->expects(self::once())
			->method('groupExists')
			->willReturn(true);
		$this->calendarService->expects(self::once())
			->method('shareWith')
			->with($shareable->getResourceId(), $principal, Backend::ACCESS_READ);

		$this->backend->updateShares($shareable, $add, []);
	}

	public function testUpdateShareCircle(): void {
		$shareable = $this->createConfiguredMock(IShareable::class, [
			'getOwner' => 'principals/users/alice',
			'getResourceId' => 42,
		]);
		$add = [
			[
				'href' => 'principal:principals/circles/bob',
				'readOnly' => true,
			]
		];
		$principal = 'principals/groups/bob';

		$this->shareCache->expects(self::once())
			->method('clear');
		$this->principalBackend->expects(self::once())
			->method('findByUri')
			->willReturn($principal);
		$this->userManager->expects(self::never())
			->method('userExists');
		$this->groupManager->expects(self::once())
			->method('groupExists')
			->willReturn(true);
		$this->calendarService->expects(self::once())
			->method('shareWith')
			->with($shareable->getResourceId(), $principal, Backend::ACCESS_READ);

		$this->backend->updateShares($shareable, $add, []);
	}

	public function testUnshareBob(): void {
		$shareable = $this->createConfiguredMock(IShareable::class, [
			'getOwner' => 'principals/users/alice',
			'getResourceId' => 42,
		]);
		$remove = [
			[
				'href' => 'principal:principals/users/bob',
				'readOnly' => true,
			]
		];
		$principal = 'principals/users/bob';

		$this->shareCache->expects(self::once())
			->method('clear');
		$this->principalBackend->expects(self::once())
			->method('findByUri')
			->willReturn($principal);
		$this->calendarService->expects(self::once())
			->method('deleteShare')
			->with($shareable->getResourceId(), $principal);
		$this->calendarService->expects(self::once())
			->method('hasGroupShare')
			->willReturn(false);
		$this->calendarService->expects(self::never())
			->method('unshare');

		$this->backend->updateShares($shareable, [], $remove);
	}

	public function testUnshareWithBobGroup(): void {
		$shareable = $this->createConfiguredMock(IShareable::class, [
			'getOwner' => 'principals/users/alice',
			'getResourceId' => 42,
		]);
		$remove = [
			[
				'href' => 'principal:principals/users/bob',
				'readOnly' => true,
			]
		];
		$oldShares = [
			[
				'href' => 'principal:principals/groups/bob',
				'commonName' => 'bob',
				'status' => 1,
				'readOnly' => true,
				'{http://owncloud.org/ns}principal' => 'principals/groups/bob',
				'{http://owncloud.org/ns}group-share' => true,
			]
		];


		$this->shareCache->expects(self::once())
			->method('clear');
		$this->principalBackend->expects(self::once())
			->method('findByUri')
			->willReturn('principals/users/bob');
		$this->calendarService->expects(self::once())
			->method('deleteShare')
			->with($shareable->getResourceId(), 'principals/users/bob');
		$this->calendarService->expects(self::once())
			->method('hasGroupShare')
			->with($oldShares)
			->willReturn(true);
		$this->calendarService->expects(self::once())
			->method('unshare')
			->with($shareable->getResourceId(), 'principals/users/bob');

		$this->backend->updateShares($shareable, [], $remove, $oldShares);
	}

	public function testGetShares(): void {
		$resourceId = 42;
		$principal = 'principals/groups/bob';
		$rows = [
			[
				'principaluri' => $principal,
				'access' => Backend::ACCESS_READ,
			]
		];
		$expected = [
			[
				'href' => 'principal:principals/groups/bob',
				'commonName' => 'bob',
				'status' => 1,
				'readOnly' => true,
				'{http://owncloud.org/ns}principal' => $principal,
				'{http://owncloud.org/ns}group-share' => true,
			]
		];


		$this->shareCache->expects(self::once())
			->method('get')
			->with((string)$resourceId)
			->willReturn(null);
		$this->calendarService->expects(self::once())
			->method('getShares')
			->with($resourceId)
			->willReturn($rows);
		$this->principalBackend->expects(self::once())
			->method('getPrincipalByPath')
			->with($principal)
			->willReturn(['uri' => $principal, '{DAV:}displayname' => 'bob']);
		$this->shareCache->expects(self::once())
			->method('set')
			->with((string)$resourceId, $expected);

		$result = $this->backend->getShares($resourceId);
		$this->assertEquals($expected, $result);
	}

	public function testGetSharesAddressbooks(): void {
		$service = $this->createMock(\OCA\DAV\CardDAV\Sharing\Service::class);
		$backend = new ContactsSharingBackend(
			$this->userManager,
			$this->groupManager,
			$this->principalBackend,
			$this->cacheFactory,
			$service,
			$this->logger);
		$resourceId = 42;
		$principal = 'principals/groups/bob';
		$rows = [
			[
				'principaluri' => $principal,
				'access' => Backend::ACCESS_READ,
			]
		];
		$expected = [
			[
				'href' => 'principal:principals/groups/bob',
				'commonName' => 'bob',
				'status' => 1,
				'readOnly' => true,
				'{http://owncloud.org/ns}principal' => $principal,
				'{http://owncloud.org/ns}group-share' => true,
			]
		];

		$this->shareCache->expects(self::once())
			->method('get')
			->with((string)$resourceId)
			->willReturn(null);
		$service->expects(self::once())
			->method('getShares')
			->with($resourceId)
			->willReturn($rows);
		$this->principalBackend->expects(self::once())
			->method('getPrincipalByPath')
			->with($principal)
			->willReturn(['uri' => $principal, '{DAV:}displayname' => 'bob']);
		$this->shareCache->expects(self::once())
			->method('set')
			->with((string)$resourceId, $expected);

		$result = $backend->getShares($resourceId);
		$this->assertEquals($expected, $result);
	}

	public function testPreloadShares(): void {
		$resourceIds = [42, 99];
		$rows = [
			[
				'resourceid' => 42,
				'principaluri' => 'principals/groups/bob',
				'access' => Backend::ACCESS_READ,
			],
			[
				'resourceid' => 99,
				'principaluri' => 'principals/users/carlos',
				'access' => Backend::ACCESS_READ_WRITE,
			]
		];
		$principalResults = [
			['uri' => 'principals/groups/bob', '{DAV:}displayname' => 'bob'],
			['uri' => 'principals/users/carlos', '{DAV:}displayname' => 'carlos'],
		];

		$this->shareCache->expects(self::exactly(2))
			->method('get')
			->willReturn(null);
		$this->calendarService->expects(self::once())
			->method('getSharesForIds')
			->with($resourceIds)
			->willReturn($rows);
		$this->principalBackend->expects(self::exactly(2))
			->method('getPrincipalByPath')
			->willReturnCallback(function (string $principal) use ($principalResults) {
				switch ($principal) {
					case 'principals/groups/bob':
						return $principalResults[0];
					default:
						return $principalResults[1];
				}
			});
		$this->shareCache->expects(self::exactly(2))
			->method('set');

		$this->backend->preloadShares($resourceIds);
	}
}
