<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\integration\DAV\Sharing;

use OC\Memcache\NullCache;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\Federation\FederationSharingService;
use OCA\DAV\CalDAV\Sharing\Service;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\RemoteUserPrincipalBackend;
use OCA\DAV\DAV\Sharing\Backend;
use OCA\DAV\DAV\Sharing\SharingMapper;
use OCA\DAV\DAV\Sharing\SharingService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class CalDavSharingBackendTest extends TestCase {

	private IDBConnection $db;
	private IUserManager $userManager;
	private IGroupManager $groupManager;
	private Principal $principalBackend;
	private ICacheFactory $cacheFactory;
	private LoggerInterface $logger;
	private SharingMapper $sharingMapper;
	private SharingService $sharingService;
	private Backend $sharingBackend;
	private RemoteUserPrincipalBackend&MockObject $remoteUserPrincipalBackend;
	private FederationSharingService&MockObject $federationSharingService;

	private $resourceIds = [10001];

	protected function setUp(): void {
		parent::setUp();

		$this->db = Server::get(IDBConnection::class);

		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->principalBackend = $this->createMock(Principal::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cacheFactory->method('createInMemory')
			->willReturn(new NullCache());
		$this->logger = new \Psr\Log\NullLogger();
		$this->remoteUserPrincipalBackend = $this->createMock(RemoteUserPrincipalBackend::class);
		$this->federationSharingService = $this->createMock(FederationSharingService::class);

		$this->sharingMapper = new SharingMapper($this->db);
		$this->sharingService = new Service($this->sharingMapper);

		$this->sharingBackend = new \OCA\DAV\CalDAV\Sharing\Backend(
			$this->userManager,
			$this->groupManager,
			$this->principalBackend,
			$this->remoteUserPrincipalBackend,
			$this->cacheFactory,
			$this->sharingService,
			$this->federationSharingService,
			$this->logger
		);

		$this->removeFixtures();
	}

	protected function tearDown(): void {
		$this->removeFixtures();
	}

	protected function removeFixtures(): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete('dav_shares')
			->where($qb->expr()->in('resourceid', $qb->createNamedParameter($this->resourceIds, IQueryBuilder::PARAM_INT_ARRAY)));
		$qb->executeStatement();
	}

	public function testShareCalendarWithGroup(): void {
		$calendar = $this->createMock(Calendar::class);
		$calendar->method('getResourceId')
			->willReturn(10001);
		$calendar->method('getOwner')
			->willReturn('principals/users/admin');

		$this->principalBackend->method('findByUri')
			->willReturn('principals/groups/alice_bob');

		$this->groupManager->method('groupExists')
			->willReturn(true);

		$this->sharingBackend->updateShares(
			$calendar,
			[['href' => 'principals/groups/alice_bob']],
			[],
			[]
		);

		$this->assertCount(1, $this->sharingService->getShares(10001));
	}

	public function testUnshareCalendarFromGroup(): void {
		$calendar = $this->createMock(Calendar::class);
		$calendar->method('getResourceId')
			->willReturn(10001);
		$calendar->method('getOwner')
			->willReturn('principals/users/admin');

		$this->principalBackend->method('findByUri')
			->willReturn('principals/groups/alice_bob');

		$this->groupManager->method('groupExists')
			->willReturn(true);

		$this->sharingBackend->updateShares(
			shareable: $calendar,
			add: [['href' => 'principals/groups/alice_bob']],
			remove: [],
		);

		$this->assertCount(1, $this->sharingService->getShares(10001));

		$this->sharingBackend->updateShares(
			shareable: $calendar,
			add: [],
			remove: ['principals/groups/alice_bob'],
		);

		$this->assertCount(0, $this->sharingService->getShares(10001));
	}

	public function testShareCalendarWithGroupAndUnshareAsUser(): void {
		$calendar = $this->createMock(Calendar::class);
		$calendar->method('getResourceId')
			->willReturn(10001);
		$calendar->method('getOwner')
			->willReturn('principals/users/admin');

		$this->principalBackend->method('findByUri')
			->willReturnMap([
				['principals/groups/alice_bob', '', 'principals/groups/alice_bob'],
				['principals/users/bob', '', 'principals/users/bob'],
			]);
		$this->principalBackend->method('getGroupMembership')
			->willReturn([
				'principals/groups/alice_bob',
			]);
		$this->principalBackend->method('getCircleMembership')
			->willReturn([]);

		$this->groupManager->method('groupExists')
			->willReturn(true);

		/*
		 * Owner is sharing the calendar with a group.
		 */
		$this->sharingBackend->updateShares(
			shareable: $calendar,
			add: [['href' => 'principals/groups/alice_bob']],
			remove: [],
		);

		$this->assertCount(1, $this->sharingService->getShares(10001));

		/*
		 * Member of the group unshares the calendar.
		 */
		$this->sharingBackend->unshare(
			shareable: $calendar,
			principalUri: 'principals/users/bob'
		);

		$this->assertCount(1, $this->sharingService->getShares(10001));
		$this->assertCount(1, $this->sharingService->getUnshares(10001));
	}

	/**
	 * Tests the functionality of sharing a calendar with a user, then with a group (that includes the shared user),
	 * and subsequently unsharing it from the individual user. Verifies that the unshare operation correctly removes the specific user share
	 * without creating an additional unshare entry.
	 */
	public function testShareCalendarWithUserThenGroupThenUnshareUser(): void {
		$calendar = $this->createMock(Calendar::class);
		$calendar->method('getResourceId')
			->willReturn(10001);
		$calendar->method('getOwner')
			->willReturn('principals/users/admin');

		$this->principalBackend->method('findByUri')
			->willReturnMap([
				['principals/groups/alice_bob', '', 'principals/groups/alice_bob'],
				['principals/users/bob', '', 'principals/users/bob'],
			]);
		$this->principalBackend->method('getGroupMembership')
			->willReturn([
				'principals/groups/alice_bob',
			]);
		$this->principalBackend->method('getCircleMembership')
			->willReturn([]);

		$this->userManager->method('userExists')
			->willReturn(true);
		$this->groupManager->method('groupExists')
			->willReturn(true);

		/*
		 * Step 1) The owner shares the calendar with a user.
		 */
		$this->sharingBackend->updateShares(
			shareable: $calendar,
			add: [['href' => 'principals/users/bob']],
			remove: [],
		);

		$this->assertCount(1, $this->sharingService->getShares(10001));

		/*
		 * Step 2) The owner shares the calendar with a group that includes the
		 * user from step 1 as a member.
		 */
		$this->sharingBackend->updateShares(
			shareable: $calendar,
			add: [['href' => 'principals/groups/alice_bob']],
			remove: [],
		);

		$this->assertCount(2, $this->sharingService->getShares(10001));

		/*
		 * Step 3) Unshare the calendar from user as owner.
		 */
		$this->sharingBackend->updateShares(
			shareable: $calendar,
			add: [],
			remove: ['principals/users/bob'],
		);

		/*
		 * The purpose of this test is to ensure that removing a user from a share, as the owner, does not result in an "unshare" row being added.
		 * Instead, the actual user share should be removed.
		 */
		$this->assertCount(1, $this->sharingService->getShares(10001));
		$this->assertCount(0, $this->sharingService->getUnshares(10001));
	}

}
