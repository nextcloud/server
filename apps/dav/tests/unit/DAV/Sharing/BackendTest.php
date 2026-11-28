<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\DAV\Sharing;

use OCA\DAV\CalDAV\Federation\FederationSharingService;
use OCA\DAV\CalDAV\Sharing\Backend as CalendarSharingBackend;
use OCA\DAV\CalDAV\Sharing\Service;
use OCA\DAV\CardDAV\Sharing\Backend as ContactsSharingBackend;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\RemoteUserPrincipalBackend;
use OCA\DAV\DAV\Sharing\Backend;
use OCA\DAV\DAV\Sharing\IShareable;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IManager as IShareManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\Forbidden;
use Test\TestCase;

class BackendTest extends TestCase {

	private IDBConnection&MockObject $db;
	private IUserManager&MockObject $userManager;
	private IGroupManager&MockObject $groupManager;
	private Principal&MockObject $principalBackend;
	private ICache&MockObject $shareCache;
	private LoggerInterface&MockObject $logger;
	private ICacheFactory&MockObject $cacheFactory;
	private Service&MockObject $calendarService;
	private RemoteUserPrincipalBackend&MockObject $remoteUserPrincipalBackend;
	private FederationSharingService&MockObject $federationSharingService;
	private IShareManager&MockObject $shareManager;
	private IUserSession&MockObject $userSession;
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
		$this->remoteUserPrincipalBackend = $this->createMock(RemoteUserPrincipalBackend::class);
		$this->federationSharingService = $this->createMock(FederationSharingService::class);
		$this->shareManager = $this->createMock(IShareManager::class);
		$this->shareManager->method('shareApiEnabled')->willReturn(true);
		$this->shareManager->method('allowGroupSharing')->willReturn(true);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->backend = new CalendarSharingBackend(
			$this->userManager,
			$this->groupManager,
			$this->principalBackend,
			$this->remoteUserPrincipalBackend,
			$this->cacheFactory,
			$this->calendarService,
			$this->federationSharingService,
			$this->logger,
			$this->shareManager,
			$this->userSession,
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
			->method('getPrincipalPropertiesByPath')
			->willReturn(['uri' => $principal]);
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
			->method('getPrincipalPropertiesByPath')
			->willReturn(['uri' => $principal]);
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
			->method('getPrincipalPropertiesByPath')
			->willReturn(['uri' => $principal]);
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
			->method('getPrincipalPropertiesByPath')
			->willReturn(['uri' => $principal]);
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
			->method('getPrincipalPropertiesByPath')
			->willReturn(['uri' => $principal]);
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
			'principal:principals/users/bob',
		];
		$principal = 'principals/users/bob';

		$this->shareCache->expects(self::once())
			->method('clear');
		$this->principalBackend->expects(self::once())
			->method('getPrincipalPropertiesByPath')
			->willReturn(['uri' => $principal]);
		$this->calendarService->expects(self::once())
			->method('deleteShare')
			->with($shareable->getResourceId(), $principal);
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
			'principal:principals/users/bob',
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
			->method('getPrincipalPropertiesByPath')
			->with('principals/users/bob', ['uri'])
			->willReturn(['uri' => 'principals/users/bob']);
		$this->calendarService->expects(self::once())
			->method('deleteShare')
			->with($shareable->getResourceId(), 'principals/users/bob');
		$this->calendarService->expects(self::never())
			->method('unshare');

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
			->method('getPrincipalPropertiesByPath')
			->with($principal, ['uri', '{DAV:}displayname'])
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
			$this->remoteUserPrincipalBackend,
			$this->cacheFactory,
			$service,
			$this->federationSharingService,
			$this->logger,
			$this->shareManager,
			$this->userSession);
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
			->method('getPrincipalPropertiesByPath')
			->with($principal, ['uri', '{DAV:}displayname'])
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
			->method('getPrincipalPropertiesByPath')
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

	private function createShareManager(array $settings): IShareManager&MockObject {
		$settings += [
			'shareApiEnabled' => true,
			'sharingDisabledForUser' => false,
			'allowGroupSharing' => true,
			'shareWithGroupMembersOnly' => false,
			'shareWithGroupMembersOnlyExcludeGroupsList' => [],
		];
		$shareManager = $this->createMock(IShareManager::class);
		foreach ($settings as $method => $value) {
			$shareManager->method($method)->willReturn($value);
		}
		return $shareManager;
	}

	private function createBackendWithSettings(array $settings, ?string $currentUser = 'alice'): CalendarSharingBackend {
		$this->shareManager = $this->createShareManager($settings);
		if ($currentUser !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($currentUser);
			$this->userSession->method('getUser')->willReturn($user);
		}

		$users = [];
		foreach (['alice', 'bob', 'carol'] as $uid) {
			$users[$uid] = $this->createMock(IUser::class);
			$users[$uid]->method('getUID')->willReturn($uid);
		}
		$this->userManager->method('userExists')->willReturn(true);
		$this->userManager->method('get')->willReturnCallback(fn (string $uid) => $users[$uid] ?? null);
		$this->groupManager->method('groupExists')->willReturn(true);
		$this->groupManager->method('getUserGroupIds')->willReturnCallback(fn (IUser $user) => match ($user->getUID()) {
			'alice' => ['alice', 'staff', 'everyone'],
			'bob' => ['bob', 'everyone'],
			'carol' => ['carol', 'staff'],
		});
		$this->principalBackend->method('getPrincipalPropertiesByPath')
			->willReturnCallback(fn (string $path) => ['uri' => $path]);

		return new CalendarSharingBackend(
			$this->userManager,
			$this->groupManager,
			$this->principalBackend,
			$this->remoteUserPrincipalBackend,
			$this->cacheFactory,
			$this->calendarService,
			$this->federationSharingService,
			$this->logger,
			$this->shareManager,
			$this->userSession,
		);
	}

	public static function dataUpdateSharesRestrictions(): array {
		$membersOnly = ['shareWithGroupMembersOnly' => true];
		$membersOnlyExcludeEveryone = [
			'shareWithGroupMembersOnly' => true,
			'shareWithGroupMembersOnlyExcludeGroupsList' => ['everyone'],
		];
		return [
			'no restrictions, user' => [[], 'principals/users/bob', true],
			'no restrictions, group' => [[], 'principals/groups/bob', true],
			'sharing api disabled' => [['shareApiEnabled' => false], 'principals/users/bob', false],
			'sharing disabled for user' => [['sharingDisabledForUser' => true], 'principals/users/bob', false],
			'sharing disabled for user, group' => [['sharingDisabledForUser' => true], 'principals/groups/staff', false],
			'group sharing disabled, group' => [['allowGroupSharing' => false], 'principals/groups/staff', false],
			'group sharing disabled, user' => [['allowGroupSharing' => false], 'principals/users/bob', true],
			'members only, common group' => [$membersOnly, 'principals/users/carol', true],
			'members only, common group everyone' => [$membersOnly, 'principals/users/bob', true],
			'members only, own group' => [$membersOnly, 'principals/groups/staff', true],
			'members only, foreign group' => [$membersOnly, 'principals/groups/bob', false],
			'members only exclude, common group' => [$membersOnlyExcludeEveryone, 'principals/users/carol', true],
			'members only exclude, only excluded group in common' => [$membersOnlyExcludeEveryone, 'principals/users/bob', false],
			'members only exclude, excluded own group' => [$membersOnlyExcludeEveryone, 'principals/groups/everyone', false],
			'members only exclude, own group' => [$membersOnlyExcludeEveryone, 'principals/groups/staff', true],
		];
	}

	#[DataProvider(methodName: 'dataUpdateSharesRestrictions')]
	public function testUpdateSharesRestrictions(array $settings, string $principal, bool $allowed): void {
		$backend = $this->createBackendWithSettings($settings);
		$shareable = $this->createConfiguredMock(IShareable::class, [
			'getOwner' => 'principals/users/alice',
			'getResourceId' => 42,
		]);

		if ($allowed) {
			$this->calendarService->expects(self::once())
				->method('shareWith')
				->with(42, $principal, Backend::ACCESS_READ);
		} else {
			$this->calendarService->expects(self::never())
				->method('shareWith');
			$this->expectException(Forbidden::class);
		}

		$backend->updateShares($shareable, [['href' => 'principal:' . $principal, 'commonName' => '', 'readOnly' => true]], []);
	}

	public function testUpdateSharesMembersOnlyWithoutSession(): void {
		$backend = $this->createBackendWithSettings(['shareWithGroupMembersOnly' => true], null);
		$shareable = $this->createConfiguredMock(IShareable::class, [
			'getOwner' => 'principals/users/alice',
			'getResourceId' => 42,
		]);

		$this->calendarService->expects(self::never())
			->method('shareWith');
		$this->expectException(Forbidden::class);

		$backend->updateShares($shareable, [['href' => 'principal:principals/users/carol', 'commonName' => '', 'readOnly' => true]], []);
	}

	public function testUpdateSharesRemoteUserIgnoresRestrictions(): void {
		$backend = $this->createBackendWithSettings([
			'sharingDisabledForUser' => true,
			'shareWithGroupMembersOnly' => true,
		]);
		$shareable = $this->createConfiguredMock(IShareable::class, [
			'getOwner' => 'principals/users/alice',
			'getResourceId' => 42,
		]);
		$principal = 'principals/remote-users/abcdef';

		$this->remoteUserPrincipalBackend->method('getPrincipalByPath')
			->with($principal)
			->willReturn(['uri' => $principal]);
		$this->federationSharingService->expects(self::once())
			->method('shareWith')
			->with($shareable, $principal, Backend::ACCESS_READ);

		$backend->updateShares($shareable, [['href' => 'principal:' . $principal, 'commonName' => '', 'readOnly' => true]], []);
	}

	public function testRemoveSharesIgnoresRestrictions(): void {
		$backend = $this->createBackendWithSettings([
			'shareApiEnabled' => false,
			'sharingDisabledForUser' => true,
			'allowGroupSharing' => false,
			'shareWithGroupMembersOnly' => true,
		]);
		$shareable = $this->createConfiguredMock(IShareable::class, [
			'getOwner' => 'principals/users/alice',
			'getResourceId' => 42,
		]);

		$deleted = [];
		$this->calendarService->expects(self::exactly(2))
			->method('deleteShare')
			->willReturnCallback(function (int $resourceId, string $principal) use (&$deleted): void {
				$deleted[] = $principal;
			});

		$backend->updateShares($shareable, [], ['principal:principals/users/bob', 'principal:principals/groups/bob']);
		$this->assertEquals(['principals/users/bob', 'principals/groups/bob'], $deleted);
	}

	public function testUnshareIgnoresRestrictions(): void {
		$backend = $this->createBackendWithSettings([
			'shareApiEnabled' => false,
			'shareWithGroupMembersOnly' => true,
		], null);
		$shareable = $this->createConfiguredMock(IShareable::class, [
			'getOwner' => 'principals/users/alice',
			'getResourceId' => 42,
		]);

		$this->calendarService->expects(self::once())
			->method('deleteShare')
			->with(42, 'principals/users/bob');
		$this->principalBackend->method('getGroupMembership')->willReturn([]);
		$this->principalBackend->method('getCircleMembership')->willReturn([]);
		$this->calendarService->method('getShares')->willReturn([]);

		$this->assertTrue($backend->unshare($shareable, 'principal:principals/users/bob'));
	}
}
